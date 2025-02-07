<?php

if (!defined("IN_MYBB")) {
    die("Direct access not allowed.");
}

function search_blocker_info() {
    return array(
        "name" => "Search Blocker",
        "description" => "Blocks certain search terms.",
        "website" => "",
        "author" => "Mellon",
        "authorsite" => "",
        "version" => "1.0",
        "compatibility" => "18*"
    );
}

function search_blocker_install() {
    global $db;
    
    $settings_group = [
        'name' => 'search_blocker',
        'title' => 'Search Blocker',
        'description' => 'Search Blocker settings.',
        'disporder' => 1,
        'isdefault' => 0,
    ];

    $gid = $db->insert_query('settinggroups', $settings_group);
    
    $setting_array = [
        'blocked_keywords' => [
            'title' => 'Blocked Keywords',
            'description' => 'Enter keywords to block, separated by commas.',
            'optionscode' => 'text',
            'value' => '',
            'disporder' => 1,
            'gid' => intval($gid),
        ],
    ];
	
    foreach ($setting_array as $name => $setting) {
        $setting['name'] = $name;
        $db->insert_query('settings', $setting);
    }

    rebuild_settings();
}

function search_blocker_is_installed() {
    global $db;

    $query = $db->simple_select('settinggroups', 'gid', "name = 'search_blocker'");
    $setting_group = $db->fetch_array($query);
    return !empty($setting_group);
}

function search_blocker_uninstall() {
    global $db;

    $db->delete_query('settings', "name = 'blocked_keywords'");
    $db->delete_query('settinggroups', "name = 'search_blocker'");

    rebuild_settings();
}

function search_blocker_activate() {
}

function search_blocker_deactivate() {
}

function search_blocker_message_check() {
	global $mybb;

	if (!isset($mybb->settings['blocked_keywords'])) {
        return;
    }
	
    $blocked_keywords = explode(',', $mybb->settings['blocked_keywords']);
    $blocked_keywords = array_map('trim', $blocked_keywords);

    if (isset($mybb->input['keywords'])) {
        foreach ($blocked_keywords as $keyword) {
            if (!empty($keyword) && stripos($mybb->input['keywords'], $keyword) !== false) {
                error("Get lost!", "Search Results");
				exit;
            }
        }
    }
}
$plugins->add_hook('search_do_search_process', 'search_blocker_message_check');

?>
