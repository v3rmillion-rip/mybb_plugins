<?php
define('IN_MYBB', 1);
require_once "global.php";

$client_id = "REDACTED";
$client_secret = "REDACTED";
$redirect_uri = "https://v3rmillion.rip/discord_verify.php";
$discord_invite = "https://v3rm.rip/discord";
$time = time(); 

session_start();

// Not logged in
if (!$mybb->user['uid']) {
    header("Location: $discord_invite");
    exit;
}


global $db;
$uid = (int)$mybb->user['uid'];
$query = $db->simple_select("users", "discord_verified", "uid = $uid");
$user = $db->fetch_array($query);

// Check if 6 hours have passed since the last verification
if (!empty($user['discord_dateline'])) {
    $time_left = floor((6 * 3600 - ($time - $user['discord_dateline'])) / 3600);
    if ($time_left > 0) { 
       header("Location: $discord_invite");
	   exit;
    }
}

if (!isset($_GET['code'])) {
	header("Location: $discord_invite");
    exit;
}

$code = $_GET['code'];
$token_url = "https://discord.com/api/oauth2/token";
$data = array(
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirect_uri,
	'scope' => 'identity'
);


$cacert_path = MYBB_ROOT . "cacert.pem";

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
curl_setopt($ch, CURLOPT_CAINFO, $cacert_path);

$response = curl_exec($ch);
if ($response === false) {
    header("Location: $discord_invite");
    exit;
}
curl_close($ch);


$token_data = json_decode($response, true);
if (isset($token_data['access_token'])) {
	$access_token = $token_data['access_token'];
    $user_url = "https://discord.com/api/users/@me";
    $options = array(
        'http' => array(
            'header' => "Authorization: Bearer $access_token",
        ),
    );
    $context  = stream_context_create($options);
    $response = file_get_contents($user_url, false, $context);
    $user_data = json_decode($response, true);
	
	if (isset($user_data['id'])) {
	
		$disc_id = $user_data['id'];
		$disc_username = $user_data['username'];
		$disc_avatar = $user_data['avatar'];
		$disc_discriminator = $user_data['discriminator'];
		
		// Check already registered
		$query = $db->simple_select('users', '*', "discord_id='{$disc_id}'");
		if ($db->num_rows($query) > 0) {

		 	while ($user = $db->fetch_array($query)) {
		 		$update_data = [
		 			'discord_verified' => 0,
		 			'discord_tag' => '',
		 			'discord_id' => '',
		 			'discord_avatar' => ''
		 		];
		 		$db->update_query('users', $update_data, "uid='{$user['uid']}'");
		 	}
		}
		 
		// Username
		$db_username = $disc_username;
		if ($disc_discriminator !== "0") {
			$db_username .= "#" + $disc_discriminator; 
		}
		
		// Download pfp
		$disc_avatar_url = "https://cdn.discordapp.com/avatars/{$disc_id}/{$disc_avatar}.png";
		$avatar_path = MYBB_ROOT . "discord_profiles/{$disc_id}.png";
		$image_data = file_get_contents($disc_avatar_url);
		if (file_put_contents($avatar_path, $image_data) === false) {
			header("Location: $discord_invite");
		}
		
	    $qpath = "discord_profiles/{$disc_id}.png";
		$db->update_query('users', array(
                        'discord_verified' => 1,
                        'discord_tag' => $disc_username,
                        'discord_id' => $disc_id,
                        'discord_avatar' => $qpath,
						'discord_dateline' => (int)$time
        ), "uid = $uid");
	   
	}
}  
header("Location: $discord_invite");
?>