<?php
if (!defined("IN_MYBB")) {
    die("Direct access is not allowed.");
}

function name_finder_info() {
    return array(
        "name"          => "Name Finder",
        "description"   => "Search users by Discord ID or previous usernames.",
        "website"       => "",
        "author"        => "",
        "authorsite"    => "",
        "version"       => "1.0",
        "compatibility" => "18*"
    );
}

function name_finder_activate() {
}

function name_finder_deactivate() {
}


$plugins->add_hook('admin_user_menu', 'name_finder_admin_user_menu');
$plugins->add_hook('admin_user_action_handler', 'name_finder_admin_user_action_handler');
$plugins->add_hook('admin_user_permissions', 'name_finder_admin_user_permissions');

function name_finder_admin_user_menu(&$sub_menu) {
    $sub_menu[] = array(
        "id" => "name_finder",
        "title" => "Name Finder",
        "link" => "index.php?module=user-name_finder"
    );
}

function name_finder_admin_user_action_handler(&$actions) {
    $actions['name_finder'] = array('active' => 'name_finder', 'file' => 'name_finder.php');
}

function name_finder_admin_user_permissions(&$admin_permissions) {
    $admin_permissions['name_finder'] = "Can search users by Discord ID or username history?";
}
