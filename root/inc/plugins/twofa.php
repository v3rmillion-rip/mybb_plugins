<?php

if (!defined("IN_MYBB")) {
    die("Direct access not allowed.");
}

function confirm_2fa_code($uid, $code) {
    global $db;

    $user = $db->fetch_array($db->simple_select("user_twofa", "*", "uid = '{$uid}'"));  
    $ga = new PHPGangsta_GoogleAuthenticator();
    $secret = $user['secret'];

    return $ga->verifyCode($secret, $code, 2);
}

/* Mybb stuff */
function twofa_info() {
    return array(
        "name" => "Two-Factor Authentication",
        "description" => "Adds two-factor authentication to MyBB.",
        "website" => "",
        "author" => "Mellon",
        "authorsite" => "",
        "version" => "1.0",
        "compatibility" => "18*",
        "codename" => "twofa",
    );
}

function twofa_install() {
    global $db;
    
    if(!$db->table_exists('user_twofa')) {
        $db->query("
            CREATE TABLE ".TABLE_PREFIX."user_twofa (
                uid INT(10) UNSIGNED NOT NULL,
                secret VARCHAR(32) NOT NULL,
                enabled TINYINT(1) NOT NULL DEFAULT 0,
				timestamp INT(10) unsigned NOT NULL,
                PRIMARY KEY (uid)
            )
        ");
    }
}

function twofa_is_installed() {
    global $db;
    return $db->table_exists('user_twofa');
}

function twofa_uninstall() {
    global $db;

    if($db->table_exists('user_twofa')) {
        $db->drop_table('user_twofa');
		$db->delete_query('templates', "title = 'usercp_2fa'");
    }
}

function twofa_activate() {
    global $db, $mybb;
   
      $template = '
<html>
<head>
<title>2FA</title>
{$headerinclude}
<script type="text/javascript">
	function confirm_2fa(action) {
		$.ajax({
			type: "POST",
			url: "2famisc.php",
			dataType: "json",
			data: { totp: document.getElementById("totpcode").value, action: action },
			success: function(response) {
				if(response.error) {
					document.getElementById("2fa_msg").innerHTML = "<strong>" + response.error + "</strong>";
				} else if (response.msg) {
					document.getElementById("2fa_msg").innerHTML = "<strong>" + response.msg + "</strong>";
					location.reload(true);
				} else {
					document.getElementById("2fa_msg").innerHTML = "<strong>Unkown error</strong>";
				}
			},
			error: function(xhr, status, error) {
				document.getElementById("2fa_msg").innerHTML = "<strong>" + error + "</strong>";
			}
		});
	}
</script>
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
    <tbody>
        <tr>
            {$usercpnav}
            <td valign="top">
                <table border="0" cellspacing="0" cellpadding="5" class="tborder">
                    <tbody>
                        <tr>
                            <td class="thead" colspan="2">
                                <strong>2 Factor Authentication</strong>
                            </td>
                        </tr>
                        <tr>
							<td align="center" class="trow1">
								<strong>Scan the QR code with your Google Authenticator app</strong>
							</td>
                        </tr>
						<tr>
							<td align="center" class="trow1">
								<img src="{$qr_code_url}" alt="QR Code">
							</td>
					    </tr>
						<tr>
							<td align="center" id="2fa_msg" class="trow1">
								<strong>{$twofa_message}</strong>
							</td>
                        </tr>
						<tr>
							<td align="center" class="trow2">
								<input type="2facode" id="totpcode" class="textbox" name="2facode" size="25" style="width: 200px;" placeholder="2FA Code" /> (<a href="2famisc.php?action=lost2fa">Lost 2FA?</a>)
							</td>
						</tr>			
                    </tbody>
                </table>
				<br>
				<div align="center" bis_skin_checked="1">
					{$two_fa_buttons}
				</div>
            </td>
        </tr>
    </tbody>
</table>
{$footer}
</body>
</html>';

    $insert_array = [
        'title' => 'usercp_2fa',
        'template' => $db->escape_string($template),
        'sid' => '-1',
        'version' => '',
        'status' => '',
        'dateline' => TIME_NOW
    ];
    $db->insert_query('templates', $insert_array);
}

function twofa_deactivate() {
}

/* 2FA Auth */
require_once MYBB_ROOT.'inc/3rdparty/2fa/GoogleAuthenticator.php';

function twofa_generate_secret($length = 32) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $secret;
}

function twofa_get_qr_code_url($user, $secret) {
    $ga = new PHPGangsta_GoogleAuthenticator();
    $qr_code_url = $ga->getQRCodeGoogleUrl('V3rmillion: ' . $user['username'], $secret);
    return $qr_code_url;
}

function twofa_qrcode() {
    global $db, $mybb, $templates, $headerinclude, $header, $footer, $usercpnav, $twofa_message;
    
    $uid = (int)$mybb->user['uid'];
    $query = $db->simple_select('user_twofa', '*', "uid='{$uid}'");
    $user_query = $db->fetch_array($query);
    $time = time();

    if (empty($user_query['secret'])) {
        $secret = twofa_generate_secret();
        $db->insert_query('user_twofa', [
            'uid' => $uid,
            'secret' => $db->escape_string($secret),
            'enabled' => 0,
			'timestamp' => 0
        ]);
    } else {
        $secret = $user_query['secret'];
    }

	if ($user_query['enabled']) {
		$query = $db->simple_select('ougc_awards_users', '*', "uid='{$uid}' AND aid=25");
		if (!$db->fetch_array($query)) {
			$db->insert_query('ougc_awards_users', [
				'uid' => $uid,
				'oid' => 1,
				'aid' => 25,
				'rid' => 0,
				'tid' => 0,
				'thread' => 0,
				'reason' => 'Enabled 2FA',
				'date' => time(),
				'disporder' => 0,
				'visible' => 1
			]);
		}
	}
	$twofa_message = ($user_query['enabled'])? "2FA is currently enabled" : "2FA is currently disabled";
    return twofa_get_qr_code_url($mybb->user, $secret);
}

function twofa_usercp()
{
    global $mybb, $db, $lang, $templates, $header, $footer, $headerinclude, $usercpnav, $qr_code_url, $twofa_message, $two_fa_buttons;

    if ($mybb->input['action'] == "2fa") {
        $uid = (int)$mybb->user['uid'];
		$query = $db->simple_select("user_twofa", "*", "uid = '{$uid}'");
		$result = $db->fetch_array($query);
		$qr_code_url = twofa_qrcode();
		if ($result && (int)$result['enabled']) {
			$twofa_message = "2FA has been enabled input code from your authenticator app below to disable 2FA.";
			$two_fa_buttons = '<input id="confirmdisable" type="submit" onclick="confirm_2fa(\'disable\');" class="button" value="Disable">';
		} else {
			$twofa_message = "Input code from your authenticator app below to activate 2FA";
			$two_fa_buttons = '<input id="confirmtotp" type="submit" onclick="confirm_2fa(\'enable\');" class="button" value="Enable">';
		}
        eval("\$usercp_2fa_page = \"" . $templates->get("usercp_2fa") . "\";");
        output_page($usercp_2fa_page);
    }
}
$plugins->add_hook('usercp_start', 'twofa_usercp');


function twofacode_field() {
	
global $twofa_login, $twofa_quicklogin;
$twofa_login = '
<tr>
<td class="trow2"><strong>2FA Code</strong><br /><span class="smalltext">Leave blank if you don\'t have 2FA enabled.</span></td>
<td class="trow2"><input type="text" class="textbox" name="otp" size="25" style="width: 200px;" placeholder="2FA code" /> (<a href="member.php?action=lost2fa">Lost 2FA?</a>)</td>
</tr>';

$twofa_quicklogin = '
<tr>
<td class="trow2"><strong>2FA Code</strong></td>
<td class="trow2"><input type="text" class="textbox" name="otp" placeholder="2FA code"> (<a href="member.php?action=lost2fa" bis_skin_checked="1">Lost 2FA?</a>)</td>
</tr>
';
}
$plugins->add_hook('global_start', 'twofacode_field');

function twofa_otp_input() {
    global $mybb;
	$mybb->input['otp'] = $mybb->get_input('otp');
}
$plugins->add_hook('member_do_login_start', 'twofa_otp_input');


function twofa_verify_login($data) {
    global $mybb, $session, $db, $lang, $plugins;
	
    if ($data->login_data['uid']) {
		
        $uid = (int)$data->login_data['uid'];
        $query = $db->simple_select("user_twofa", "*", "uid = '{$uid}'");
        $user_twofa = $db->fetch_array($query);

        if ($user_twofa && $user_twofa['enabled']) {
            if (!isset($mybb->input['otp']) || !$mybb->input['otp'] || !confirm_2fa_code($uid, $mybb->input['otp'])) {
                $data->success = false;
                $data->set_error("Invalid 2FA code.");
            }
        }
    }
}
$plugins->add_hook('datahandler_login_validate_end', 'twofa_verify_login');