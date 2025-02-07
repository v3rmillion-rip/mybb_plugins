<?php
// Prevent direct access to the file
if (!defined('IN_MYBB')) {
    die('Direct access to this file is not allowed.');
}

function admin_unread_info() {
    return array(
        "name" => "Admin Unread PM",
        "description" => "Checks if user has an unread PM from an admin.",
        "website" => "",
        "author" => "Mellon",
        "authorsite" => "",
        "version" => "1.0",
        "compatibility" => "18*"
    );
}


function admin_unread_install() {
}

function admin_unread_uninstall() {
}

function admin_unread_activate() {
}


function admin_unread_deactivate() {
}

function admin_unread_check_pm() {
    global $mybb, $db, $templatelist, $pm_warning;

    if ($mybb->user['uid'] > 0) {
        $uid = (int)$mybb->user['uid'];

        $query = $db->simple_select("privatemessages", "*", "toid = $uid AND folder = '1' AND status = '0'");
        $pm = $db->fetch_array($query);

		$query = $db->simple_select("privatemessages", "*", "toid = $uid AND folder = '1' AND status = '0'");
        while ($pm = $db->fetch_array($query)) {	
		
			$fromid = (int)$pm['fromid'];
			$pm_title = htmlspecialchars_uni($pm['subject']);
			$pm_id = (int)$pm['pmid'];

			$query = $db->simple_select("users", "username, usergroup", "uid = $fromid");
			$sender = $db->fetch_array($query);
			
			$query = $db->simple_select("users", "username, usergroup", "uid = $uid");
			$to = $db->fetch_array($query);

            if ($sender) {
				
			   $sender_group = $sender['usergroup'];
               $admin_groups = array(4, 3, 6, 13, 6); // Admins UGs also block unactivated and banned accounts	
               if (in_array($sender_group, $admin_groups) && $to['usergroup'] != 5 && $to['usergroup'] != 7) {
					$username = htmlspecialchars_uni($sender['username']);
					$pm_warning .= "
						<div class=\"pm_alert\">
						<a href=\"private.php?action=read&pmid={$pm_id}\";>
							<strong style=\"color:#000000;\">You have one unread private message </strong>
							<span style=\"color:#000000;\">from </span> 
							<strong>{$username} </strong>
							<span style=\"color:#000000;\">titled </span> 
							<strong>{$pm_title} </strong>
						</a>
						</div></br>";
						
				}
			}
        }
    }
}
$plugins->add_hook('global_start', 'admin_unread_check_pm');
?>