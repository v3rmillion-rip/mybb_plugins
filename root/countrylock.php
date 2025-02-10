<?php
define('IN_MYBB', 1);
require_once "global.php";

$user = $mybb->user;
$uid = (int)$user['uid'];
if (!$uid) {
    die(json_encode(array('data' => 'User not logged in')));
}

$time = time(); 
if (!empty($user['countrylock_dateline'])) {
	$time_left = floor((6 * 3600 - ($time - $user['countrylock_dateline'])) / 3600);
	if ($time_left > 0) { 
		die(json_encode(array('data' => 'Can only change lock every 6 hours.')));
	}
}
	
$country = $mybb->input['country'];
$allowed_countries = [
    "AF", "AL", "DZ", "AS", "AD", "AO", "AI", "AQ", "AG", "AR", "AM", "AW", "AU", "AT", "AZ", "BS", 
    "BH", "BD", "BB", "BY", "BE", "BZ", "BJ", "BM", "BT", "BO", "BA", "BW", "BV", "BR", "IO", "BN", 
    "BG", "BF", "BI", "KH", "CM", "CA", "CV", "KY", "CF", "TD", "CL", "CN", "CX", "CC", "CO", "KM", 
    "CG", "CD", "CK", "CR", "CI", "HR", "CU", "CY", "CZ", "DK", "DJ", "DM", "DO", "EC", "EG", "SV", 
    "GQ", "ER", "EE", "ET", "FK", "FO", "FJ", "FI", "FR", "GF", "PF", "TF", "GA", "GM", "GE", "DE", 
    "GH", "GI", "GR", "GL", "GD", "GP", "GU", "GT", "GN", "GW", "GY", "HT", "HM", "VA", "HN", "HK", 
    "HU", "IS", "IN", "ID", "IR", "IQ", "IE", "IL", "IT", "JM", "JP", "JO", "KZ", "KE", "KI", "KP", 
    "KR", "KW", "KG", "LA", "LV", "LB", "LS", "LR", "LY", "LI", "LT", "LU", "MO", "MK", "MG", "MW", 
    "MY", "MV", "ML", "MT", "MH", "MQ", "MR", "MU", "YT", "MX", "FM", "MD", "MC", "MN", "MS", "MA", 
    "MZ", "MM", "NA", "NR", "NP", "NL", "AN", "NC", "NZ", "NI", "NE", "NG", "NU", "NF", "MP", "NO", 
    "OM", "PK", "PW", "PS", "PA", "PG", "PY", "PE", "PH", "PN", "PL", "PT", "PR", "QA", "RE", "RO", 
    "RU", "RW", "SH", "KN", "LC", "PM", "VC", "WS", "SM", "ST", "SA", "SN", "CS", "SC", "SL", "SG", 
    "SK", "SI", "SB", "SO", "ZA", "GS", "ES", "LK", "SD", "SR", "SJ", "SZ", "SE", "CH", "SY", "TW", 
    "TJ", "TZ", "TH", "TL", "TG", "TK", "TO", "TT", "TN", "TR", "TM", "TC", "TV", "UG", "UA", "AE", 
    "GB", "US", "UM", "UY", "UZ", "VU", "VE", "VN", "VG", "VI", "WF", "EH", "YE", "ZM", "ZW"
];
if (in_array($country, $allowed_countries)) {
	$update_data = [
		'country_lock' => $country,
		'countrylock_dateline' => $time,
	];
	$db->update_query('users', $update_data, "uid='{$user['uid']}'");
	echo json_encode(array('data' => 'Current Lock: ' . $country));
	exit;
	
} else {
	$update_data = [
		'country_lock' => "",
		'countrylock_dateline' => $time,
	];
	$db->update_query('users', $update_data, "uid='{$user['uid']}'");
	echo json_encode(array('data' => 'No current country lock selected'));
	exit;
}




?>