<?php
if (!defined('IN_MYBB')) {
    die('Direct access not allowed.');
}

function ignore_last_edited_info()
{
    return [
        'name'          => 'Ignore Last Modified Info',
        'description'   => 'Prevents updating edited date of post if modified by whitelisted uids.',
        'website'       => '',
        'author'        => 'JP (yakov)',
        'authorsite'    => '',
        'version'       => '1.0',
        'guid'          => '',
        'compatibility' => '18*',
    ];
}

function ignore_last_edited_activate()
{
    global $db;

    // Add setting group
    $setting_group = [
        'name'        => 'ignore_last_edited',
        'title'       => 'Ignore Last Edited Settings',
        'description' => 'Settings for the Ignore Last Modified plugin.',
        'disporder'   => 1,
        'isdefault'   => 0,
    ];
    $db->insert_query('settinggroups', $setting_group);
    $gid = $db->insert_id();

    // Add setting to define UIDs
    $setting = [
        'name'        => 'ignore_modified_uids',
        'title'       => 'UIDs to Ignore',
        'description' => 'Comma-separated list of UIDs to ignore for last modified.',
        'optionscode' => 'text',
        'value'       => '',
        'disporder'   => 1,
        'gid'         => $gid,
    ];
    $db->insert_query('settings', $setting);

    rebuild_settings();
}

function ignore_last_edited_deactivate()
{
    global $db;

    // Remove settings
    $db->delete_query('settinggroups', "name = 'ignore_last_edited'");
    $db->delete_query('settings', "name = 'ignore_modified_uids'");

    rebuild_settings();
}

$plugins->add_hook('datahandler_post_update', 'ignore_last_edited_handler');

function ignore_last_edited_handler(&$data)
{
    global $mybb;

    // Get the list of UIDs from the settings
    $ignored_uids = explode(',', $mybb->settings['ignore_modified_uids']);
    $ignored_uids = array_map('trim', $ignored_uids);

    // Check if current user UID is in the ignored list
    if (in_array($mybb->user['uid'], $ignored_uids)) {
        unset($data->post_update_data['edituid']);
        unset($data->post_update_data['edittime']);
        unset($data->post_update_data['editreason']);
    }
}
