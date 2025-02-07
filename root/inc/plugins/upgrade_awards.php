<?php

if (!defined('IN_MYBB')) {
    die('This file cannot be accessed directly.');
}

function upgrade_awards_info()
{
    return array(
        "name"          => "Upgrade Awards",
        "description"   => "Upgrade Awards.",
        "website"       => "",
        "author"        => "Mellon",
        "authorsite"    => "",
        "version"       => "1.0",
        "guid"          => "",
        "compatibility" => "18*"
    );
}

function upgrade_awards_install()
{
}

function upgrade_awards_is_installed()
{
    return true;
}

function upgrade_awards_uninstall()
{
}

function upgrade_awards_activate() {
}

function upgrade_awards_deactivate()
{
}

function upgrade_awards_run() {
    global $mybb, $db;

    if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1) {
        return;
    }

	$uid = (int)$mybb->user['uid'];
	$user = get_user($uid);
	$ug = $user['usergroup'];
	$formattedname = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	
	// Elite
	if ($ug == 11) {
		$query = $db->simple_select('ougc_awards_users', '*', "uid='{$uid}' AND aid=28");
		if (!$db->fetch_array($query)) {
			$db->insert_query('ougc_awards_users', [
				'uid' => $uid,
				'oid' => 1,
				'aid' => 28,
				'rid' => 0,
				'tid' => 0,
				'thread' => 0,
				'reason' => 'Purchased Elite',
				'date' => time(),
				'disporder' => 0,
				'visible' => 1
			]);
		}
	} 

}
$plugins->add_hook('global_start', 'upgrade_awards_run');