<?php

if(!defined('IN_MYBB')) {
    die('Direct access not allowed.');
}

// Plugin information
function proxy_check_info() {
    return array(
        "name"          => "Proxy and Hosting Check",
        "description"   => "Checks if the user is using a proxy or hosting service during login and registration.",
        "website"       => "",
        "author"        => "JP (yakov)",
        "authorsite"    => "",
        "version"       => "1.0",
        "compatibility" => "18*"
    );
}

$plugins->add_hook('datahandler_login_complete_start', 'proxy_check_login');
$plugins->add_hook('member_do_register_start', 'proxy_check');

function proxy_check_login($data)
{
    global $mybb, $session;

    $whitelisted_uids = explode(',', $mybb->settings['proxy_check_whitelist']);
	$whitelisted_uids = array_map('trim', $whitelisted_uids);

    if (!empty($data->login_data) && in_array($data->login_data['uid'], $whitelisted_uids)) {
    } else proxy_check();
}

function proxy_check() {
    global $mybb, $session;

    // Get the user's IP address
    $user_ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];

    $api_url = "https://pro.ip-api.com/json/{$user_ip}?fields=hosting,proxy&key=REDACTED";

    $response = @file_get_contents($api_url);

    if ($response === FALSE) {
        error_log('Failed to fetch data.');
        return;
    }

    $data = json_decode($response, true);

    if ($data['proxy'] === true || $data['hosting'] == true) {
        header("Location: proxy.php");
        die();
    }
}

function proxy_check_activate() {
    global $db;

    $settings_group = array(
        'name' => 'proxy_check_settings',
        'title' => 'Proxy and Hosting Check Settings',
        'description' => 'Settings for the Proxy and Hosting Check plugin.',
        'disporder' => 1,
        'isdefault' => 0
    );
    $db->insert_query('settinggroups', $settings_group);
    $gid = $db->insert_id();

    $setting = array(
        'name' => 'proxy_check_whitelist',
        'title' => 'Users who are whitelisted from the proxy check',
        'description' => 'Enter the uids which are whitelisted, separated by commas.',
        'optionscode' => 'text',
        'value' => '',
        'disporder' => 1,
        'gid' => $gid
    );
    $db->insert_query('settings', $setting);

    rebuild_settings();
}

function proxy_check_deactivate() {
    global $db;

    $db->delete_query('settings', "name = 'proxy_check_whitelist'");
    $db->delete_query('settinggroups', "name = 'proxy_check_settings'");

    rebuild_settings();
}
?>