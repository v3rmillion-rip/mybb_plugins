<?php
/*
MIT License

Copyright (c) 2018-2019 Peter -n3veR Dziubczynski
Copyright (c) 2024 JP (yakov)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

declare(strict_types=1);

// Disallow direct access to this file for security reasons
if (!defined('IN_MYBB')) {
  die('Direct initialization of this file is not allowed.');
}

require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';

global $plugins;

$plugins->add_hook('datahandler_post_insert_post', 'bumpthread_newpost');
$plugins->add_hook('datahandler_post_insert_thread', 'bumpthread_newthread');
$plugins->add_hook('showthread_start', 'bumpthread');

function bumpthread_info(): array
{
  global $lang;
  $lang->load('bumpthread');

  return [
    'name' => $lang->name,
    'description' => implode(' ', [
      $lang->description,
      'This plugin adds the VIP / Elite \'Bump Thread\' functionality.',
    ]),
    'website' => '',
    'author' => 'JP',
    'authorsite' => '',
    'version' => '1.0',
    'compatibility' => '18*',
  ];
}

function bumpthread_settinggroups_id(): int
{
  global $db;

  $gid = $db->fetch_field($db->simple_select('settinggroups', 'gid', "name = 'bumpthread'"), 'gid');

  return (int) $gid;
}

function bumpthread_interval_types(): array
{
  global $db, $lang;
  $lang->load('bumpthread');

  return [
    60 => $db->escape_string($lang->interval_type_minute),
    3600 => $db->escape_string($lang->interval_type_hour),
    86400 => $db->escape_string($lang->interval_type_day),
  ];
}

function bumpthread_interval_types_acp(): string
{
  $types = bumpthread_interval_types();

  array_walk($types, function (&$value, $key) {
    $value = sprintf('%s=%s', $key, $value);
  });

  return implode('\r\n', $types);
}

function bumpthread_has_permission(): bool
{
  global $mybb, $thread;

  $isThreadOpen = $thread['closed'] !== '1';
  $isUserAuthorAndUpgraded = ($thread['uid'] == $mybb->user['uid'] && 
                               ($mybb->user['usergroup'] == 10 /*VIP*/ || 
                                $mybb->user['usergroup'] == 11 /*Elite*/));
  return $isThreadOpen && ($isUserAuthorAndUpgraded || $mybb->usergroup['canmodcp']);
}

function bumpthread_install(): void
{
  global $lang, $db;
  $lang->load('bumpthread');

  $db->insert_query('templates', [
    'title' => 'bumpthread_template',
    'template' => $db->escape_string('<a href="showthread.php?tid=:tid&amp;action=bump" title=":title" class="button bump_thread_button"><span>:text</span></a>'),
    'sid' => -1,
    'version' => 300,
    'dateline' => time(),
  ]);

  $disporder = $db->fetch_field($db->simple_select('settinggroups', 'MAX(disporder) AS disporder'), 'disporder');
  $disporder = (int) $disporder;

  $db->insert_query('settinggroups', [
    'name' => 'bumpthread',
    'title' => $db->escape_string($lang->settinggroups_title),
    'description' => $db->escape_string($lang->settinggroups_description),
    'disporder' => ++$disporder,
  ]);

  $db->insert_query_multiple('settings', [
    [
      'name' => 'bumpthread_time',
      'optionscode' => 'numeric',
      'value' => 2,
      'title' => $db->escape_string($lang->setting_time_title),
      'description' => $db->escape_string($lang->setting_time_description),
      'disporder' => 1,
      'gid' => $db->insert_id(),
    ],
    [
      'name' => 'bumpthread_time_type',
      'optionscode' => sprintf('select \r\n%s', bumpthread_interval_types_acp()),
      'value' => 86400,
      'title' => $db->escape_string($lang->setting_time_type_title),
      'description' => $db->escape_string($lang->setting_time_type_description),
      'disporder' => 2,
      'gid' => $db->insert_id(),
    ],
    [
      'name' => 'bumpthread_forums',
      'optionscode' => 'forumselect',
      'value' => '',
      'title' => $db->escape_string($lang->setting_forums_title),
      'description' => $db->escape_string($lang->setting_forums_description),
      'disporder' => 3,
      'gid' => $db->insert_id(),
    ],
  ]);

  rebuild_settings();
}

function bumpthread_is_installed(): bool
{
  return !empty(bumpthread_settinggroups_id());
}

function bumpthread_uninstall(): void
{
  global $db;

  $gid = bumpthread_settinggroups_id();

  if (empty($gid)) {
    return;
  }

  $db->delete_query('templates', "title = 'bumpthread_template'");
  $db->delete_query('settings', "gid = '{$gid}'");
  $db->delete_query('settinggroups', "gid = '{$gid}'");

  rebuild_settings();
}

function bumpthread_newpost(\PostDataHandler $handler): void
{
  return;
}

function bumpthread_newthread(\PostDataHandler $handler): void
{
  return;
}

function bumpthread(): void
{
  global $lang, $mybb, $thread;
  $lang->load('bumpthread');

  $forums_allowed = (string) ($mybb->settings['bumpthread_forums'] ?? '');

  if (empty($forums_allowed)) {
    return;
  }

  $fid = (string) ($thread['fid'] ?? '');

  if ($forums_allowed === '-1') {
    $forums_allowed = $fid;
  }

  if (!in_array($fid, explode(',', $forums_allowed), true)) {
    return;
  }

  $interval = (int) ($mybb->settings['bumpthread_time'] ?? 0);
  $interval_type = (int) ($mybb->settings['bumpthread_time_type'] ?? 0);
  $bump = (int) ($thread['lastpost'] ?? 0) + ($interval * $interval_type);

  $action = (string) ($mybb->input['action'] ?? '');

  if (!empty($action) && $action === 'bump') {
    bumpthread_run($bump, $interval, bumpthread_interval_types()[$interval_type] ?? '');
    return;
  }

  bumpthread_show_button($bump);
}

function bumpthread_run(int $bump, int $interval, string $interval_type): void
{
  global $lang, $thread, $db;
  $lang->load('bumpthread');

  if ($bump > time()) {
    error($lang->sprintf($lang->interval_error, $interval, $interval_type));
    return;
  }

  if (!bumpthread_has_permission()) {
    error_no_permission();
    return;
  }

  $tid = (string) ($thread['tid'] ?? '');

  if (empty($tid)) {
    error_no_permission();
    return;
  }

  $db->update_query('threads', ['lastpost' => time()], "tid={$tid}");
  header("Location: /showthread.php?tid={$tid}");
  exit;
}

function bumpthread_show_button(int $bump): void
{
  global $lang, $bumpthread, $tid, $templates;
  $lang->load('bumpthread');

  if (!bumpthread_has_permission()) {
    return;
  }

  $template = stripslashes($templates->get('bumpthread_template'));
  $bumpthread = str_replace([':tid', ':title', ':text'], [$tid, $lang->bump_title, $lang->bump], $template);
}