<?php

if (!defined('IN_MYBB')) {
    die('This file cannot be accessed directly.');
}

function multiacc_info()
{
    return array(
        "name"          => "Detect multiple account registration",
        "description"   => "Detect multiple accounts per user",
        "website"       => "",
        "author"        => "JP and Mellon",
        "authorsite"    => "",
        "version"       => "1.0",
        "guid"          => "",
        "compatibility" => "18*"
    );
}

function multiacc_install()
{ 
	global $db;

    if (!$db->table_exists('multiaccounts')) {
        $db->write_query("
            CREATE TABLE `" . TABLE_PREFIX . "multiaccounts` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `uid` int(10) unsigned NOT NULL,
                `alt_uid` int(10) unsigned NOT NULL,
                `certainty` TINYINT(1) NOT NULL DEFAULT 1,
                `timestamp` BIGINT unsigned NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `uid` (`uid`), 
                KEY `alt_uid` (`alt_uid`)
            ) ENGINE=InnoDB " . $db->build_create_table_collation() . ";
        ");
    }    
	
	$query = $db->simple_select('tasks', 'tid', "file='multiacc_run'");
    if ($db->num_rows($query) == 0) {
		$new_task = array(
			'title'       => 'Multi-Accounts Check',
			'file'        => 'multiacc_run',
			'description' => 'Checks for multi-accounts based on IP history.',
			'minute'      => '0,5,10,15,20,25,30,35,40,45,50,55',
			'hour'        => '*',
			'day'         => '*',
			'weekday'     => '*',
			'month'       => '*',
			'nextrun'     => TIME_NOW + 180,
			'lastrun'     => 0,
			'enabled'     => 1,
			'logging'     => 1,
			'locked'      => 0,
		);
        $db->insert_query('tasks', $new_task);
    }
}

function multiacc_is_installed()
{
    global $db;
	$query = $db->simple_select('tasks', 'tid', "file='multiacc_run'");
    return $db->table_exists('multiaccounts') && $db->num_rows($query) > 0;
}

function multiacc_uninstall()
{
}

function multiacc_activate()
{
	global $db;

	$template_content = '<html>
<head>
<title>{$mybb->settings[\'bbname\']} - Multiple accounts of {$username}</title>
{$headerinclude}
</head>
<body>
{$header}
{$multipage}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
    <tr>
        <td class="thead" colspan="{$colspan}"><strong>Multiple accounts of {$username}</strong></td>
    </tr>
    <tr>
        <td class="tcat"><strong>Accounts</strong></td>
    </tr>
	<tr>
		{$multi_accounts}
    </tr>
</table>
{$footer}
</body>
</html>';
    $template_array = array(
        'title'     => 'misc_viewmultiaccounts',
        'template'  => $db->escape_string($template_content),
        'sid'       => '-1',
        'version'   => '',
        'dateline'  => TIME_NOW
    );
    $db->insert_query("templates", $template_array);

    $settings_group = array(
        'name' => 'multiaccounts_settings',
        'title' => 'MultiAccounts settings',
        'description' => 'Settings for configuring MultiAccounts plugin.',
        'disporder' => 1,
        'isdefault' => 0
    );
    $db->insert_query('settinggroups', $settings_group);
    $gid = $db->insert_id();

    $setting = array(
        'name' => 'multiaccounts_excluded_uids',
        'title' => 'Excluded UIDs',
        'description' => 'Enter the UIDs to be excluded from MultiAccounts, separated by commas.',
        'optionscode' => 'text',
        'value' => '',
        'disporder' => 1,
        'gid' => $gid
    );
    $db->insert_query('settings', $setting);
	
    rebuild_settings();
}

function multiacc_deactivate()
{
	global $db;

    $db->delete_query("templates", "title = 'misc_viewmultiaccounts'");
    $db->delete_query('settinggroups', "name = 'multiaccounts_settings'");
    $db->delete_query('settings', "name = 'multiaccounts_excluded_uids'");

    rebuild_settings();
}

$plugins->add_hook("misc_start", "multiacc_page");
function multiacc_page() {
    global $mybb, $db, $lang, $templates, $theme, $headerinclude, $header, $footer, $multipage, $report_link, $delete_link;

    if ($mybb->input['action'] == "multi") {
        $num_accs = 0;
        $colspan = 5;
        $username = "no one";
        $multi_accounts = "<tr>";

        $uid = $mybb->get_input('uid', MyBB::INPUT_INT);
        $excluded_uids = explode(',', $mybb->settings['multiaccounts_excluded_uids']);
        $excluded_uids = array_map('trim', $excluded_uids);

        if (!empty($uid) && (!in_array($uid, $excluded_uids) || $mybb->usergroup['canmodcp'])) {
            $temp_username = get_user($uid);
            if (!empty($temp_username)) {
                if ($mybb->usergroup['canmodcp'] && in_array($uid, $excluded_uids))
                {
                    $multi_accounts .= "<tr><td align=\"left\"><span style=\"color: #a2c6cc;\">Warning: This user is excluded from multi-account, you could only see this because you're a moderator.</span></td></tr>";
                }
                $username = htmlspecialchars_uni($temp_username['username']);
                $uid_query = $db->simple_select('multiaccounts', '*', "uid = '" . $db->escape_string($uid) . "'");
                while ($user = $db->fetch_array($uid_query)) {
                    if (in_array($user['alt_uid'], $excluded_uids)) continue;
                    $memprofile = get_user($user['alt_uid']);
                    $altExists = !empty($memprofile);
                    $certainty = boolval($user['certainty']) ? "Likely" : "Possible";
                    $formattedname = $altExists ? format_name($memprofile['username'], $memprofile['usergroup'], $memprofile['displaygroup']) : 'Deleted user';
                    $multi_accounts .= "<tr>
                        <td><a href=\"/member.php?action=profile&amp;uid=" . htmlspecialchars_uni($user['alt_uid']) . "\" bis_skin_checked=\"1\">{$formattedname}</a></td>
                        <td><span class='box " . strtolower($certainty) . "'>" . $certainty . "</span></td>
                        <td>" . ($user['timestamp'] ? my_date('relative', $user['timestamp']) : $lang->unknown) . "</td>
                    </tr>";
                    $num_accs++;
                }
            }
        }
        if ($num_accs === 0) {
            $multi_accounts .= "<tr><td align=\"left\"><span>No known accounts at this time.</span></td></tr>";
        }

        $multi_accounts .= "</tr>";
        $viewmultiaccounts = '';
        eval("\$viewmultiaccounts = \"" . $templates->get("misc_viewmultiaccounts") . "\";");
        output_page($viewmultiaccounts);
    }
}

$plugins->add_hook("member_profile_start", "multiacc_details");
function multiacc_details() {
    global $mybb, $db, $multiaccountsdetails, $memprofile;
	
    $uid = (int)$memprofile['uid']; // Get the user ID from the profile data
    $excluded_uids = explode(',', $mybb->settings['multiaccounts_excluded_uids']);
    $excluded_uids = array_map('trim', $excluded_uids);
    $uid_query = $db->simple_select('multiaccounts', '*', "uid = '{$uid}'");

    $certainty_found = false;
    $matches_found = false;

    while ($row = $db->fetch_array($uid_query)) {
        $matches_found = true;
        if ($row['certainty'] == 1) {
            $certainty_found = true;
            break;
        }
    }

    if ($certainty_found) {
        $multiaccountsdetails = '
		 <a href="misc.php?action=multi&amp;uid=' . $uid . '">
			   <b style="color:red;">
				<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> 
					<span class="largetext"> WARNING! This user is a multiaccounter! </span>
				<i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
			   </b>
			   <br>
               <span class="smalltext">Click here for more information</span>
	     </a>';
    } elseif ($matches_found) {
        $multiaccountsdetails = '<strong>
            <a href="misc.php?action=multi&amp;uid=' . $uid . '">
                Possible Alternate Accounts
            </a>
        </strong>';
    }

    if (!$matches_found || (in_array($uid, $excluded_uids) && !$mybb->usergroup['canmodcp']))
    {
        $multiaccountsdetails = ''; // Don't display anything
    }
}

function multiacc_run() {
    global $db;

    $query = $db->simple_select('ip_history', 'ip, GROUP_CONCAT(uid) as uids', '1=1', ['group_by' => 'ip', 'having' => 'COUNT(*) > 1']);  
    while ($row = $db->fetch_array($query)) {
        $multi = explode(',', $row['uids']);
        
        if (count($multi) > 1) {
            foreach ($multi as $uid) {
                foreach ($multi as $alt_uid) {
                    
					// Skip same
                    if ($uid == $alt_uid) continue;

                    // Check if it exists
                    $exists_query = $db->simple_select('multiaccounts', 'id', "uid={$uid} AND alt_uid={$alt_uid}");
                    if ($db->num_rows($exists_query) == 0) {
                        $insert_array = [
                            'uid' => (int)$uid,
                            'alt_uid' => (int)$alt_uid
                        ];
                        $db->insert_query('multiaccounts', $insert_array);
                    }
                }
            }
        }
    }
}