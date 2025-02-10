<?php
define('IN_MYBB', 1);
define('THIS_SCRIPT', '2famisc.php');
define("ALLOWABLE_PAGE", "lost2fa");

require_once './global.php';

$time = time();

// Lost 2fa
$action = $mybb->input['action'];
if (isset($action) && $action === 'lost2fa') {
	header("Location: https://v3rm.rip/discord");
	exit;
}

// Check params
$uid = (int)$mybb->user['uid'];
if (!$uid) {
    die(json_encode(array('error' => 'User not logged in')));
}

$totp = $mybb->input['totp'];
if (!$totp) {
    die(json_encode(array('error' => 'No code passed')));
}

// Last change
$user_2fa = $db->fetch_array($db->simple_select('user_twofa', '*', "uid='{$uid}'"));
$last_change = $user_2fa ? $user_2fa['timestamp'] : 0;	

if (isset($action)) {
    if ($action === "disable") {	
        // Check 2FA
        if (!$user_2fa) {
            die(json_encode(array('error' => '2FA is not enabled')));
        }
        
        // Check time
        if ($last_change > 0) {
            $time_left = floor((6 * 3600 - ($time - $last_change)) / 3600);
            if ($time_left > 0) { 
                die(json_encode(array('error' => 'Can only change 2FA every 6 hours.')));
            }
        }
        
        if (confirm_2fa_code($uid, $totp)) {
            echo json_encode(array('msg' => '2FA has been disabled'));
			exit;
        } else {
            die(json_encode(array('error' => 'Wrong code, re-enter 2FA code.')));
        }
        
    } else if ($action === "totp") {	
        if (confirm_2fa_code($uid, $totp)) {
            echo json_encode(array('success' => 'Code was correct'));
            exit;		
        } else {
            die(json_encode(array('error' => 'Wrong code, re-enter 2FA code.')));
        }
    } else if ($action === "enable") {	
        if (confirm_2fa_code($uid, $totp)) {
            // Check time
            if ($last_change > 0) {
                $time_left = floor((6 * 3600 - ($time - $last_change)) / 3600);
                if ($time_left > 0) { 
                    die(json_encode(array('error' => 'Can only change 2FA every 6 hours.')));
                }
            }
            $db->update_query('user_twofa', array('enabled' => 1, 'timestamp' => $time), "uid = '{$uid}'");
            echo json_encode(array('msg' => '2FA has been enabled'));
			exit;		
        } else {
            die(json_encode(array('error' => 'Wrong code, re-enter 2FA code.')));
        }
    } else {	
		header("Location: https://v3rm.rip/discord");
		exit;
	}
}
?>