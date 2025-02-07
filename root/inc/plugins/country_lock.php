<?php
if (!defined('IN_MYBB')) {
    die('Direct access not allowed.');
}

function country_lock_info()
{
    return array(
        'name'          => 'Country Lock',
        'description'   => 'Adds a country lock feature to the UserCP to restrict login based on user\'s country.',
        'website'       => '',
        'author'        => 'Mellon',
        'authorsite'    => '',
        'version'       => '1.0',
        'compatibility' => '18*'
    );
}

function country_lock_install()
{
    global $db;
    
	if(!$db->field_exists('country_lock', 'users')) {
		$db->add_column('users', 'country_lock', 'VARCHAR(4) NOT NULL DEFAULT ""');
		$db->add_column('users', 'countrylock_dateline', 'int(10) unsigned NOT NULL DEFAULT 0');
	}	
}

function country_lock_uninstall()
{
    global $db;
    
    if($db->field_exists('country_lock', 'users')) {
        $db->drop_column('users', 'country_lock');
		$db->drop_column('users', 'countrylock_dateline');
	}
}

function country_lock_is_installed()
{
    global $db;
    return $db->field_exists('country_lock', 'users') && $db->field_exists('countrylock_dateline', 'users');
}

$countries = [
	"" => "None",
    "AF" => "Afghanistan",
    "AL" => "Albania",
    "DZ" => "Algeria",
    "AS" => "American Samoa",
    "AD" => "Andorra",
    "AO" => "Angola",
    "AI" => "Anguilla",
    "AQ" => "Antarctica",
    "AG" => "Antigua and Barbuda",
    "AR" => "Argentina",
    "AM" => "Armenia",
    "AW" => "Aruba",
    "AU" => "Australia",
    "AT" => "Austria",
    "AZ" => "Azerbaijan",
    "BS" => "Bahamas",
    "BH" => "Bahrain",
    "BD" => "Bangladesh",
    "BB" => "Barbados",
    "BY" => "Belarus",
    "BE" => "Belgium",
    "BZ" => "Belize",
    "BJ" => "Benin",
    "BM" => "Bermuda",
    "BT" => "Bhutan",
    "BO" => "Bolivia",
    "BA" => "Bosnia and Herzegovina",
    "BW" => "Botswana",
    "BV" => "Bouvet Island",
    "BR" => "Brazil",
    "IO" => "British Indian Ocean Territory",
    "BN" => "Brunei Darussalam",
    "BG" => "Bulgaria",
    "BF" => "Burkina Faso",
    "BI" => "Burundi",
    "KH" => "Cambodia",
    "CM" => "Cameroon",
    "CA" => "Canada",
    "CV" => "Cape Verde",
    "KY" => "Cayman Islands",
    "CF" => "Central African Republic",
    "TD" => "Chad",
    "CL" => "Chile",
    "CN" => "China",
    "CX" => "Christmas Island",
    "CC" => "Cocos Islands",
    "CO" => "Colombia",
    "KM" => "Comoros",
    "CG" => "Congo",
    "CD" => "Democratic Republic of the Congo",
    "CK" => "Cook Islands",
    "CR" => "Costa Rica",
    "CI" => "Cote D'Ivoire",
    "HR" => "Croatia",
    "CU" => "Cuba",
    "CY" => "Cyprus",
    "CZ" => "Czech Republic",
    "DK" => "Denmark",
    "DJ" => "Djibouti",
    "DM" => "Dominica",
    "DO" => "Dominican Republic",
    "EC" => "Ecuador",
    "EG" => "Egypt",
    "SV" => "El Salvador",
    "GQ" => "Equatorial Guinea",
    "ER" => "Eritrea",
    "EE" => "Estonia",
    "ET" => "Ethiopia",
    "FK" => "Falkland Islands",
    "FO" => "Faroe Islands",
    "FJ" => "Fiji",
    "FI" => "Finland",
    "FR" => "France",
    "GF" => "French Guiana",
    "PF" => "French Polynesia",
    "TF" => "French Southern Territories",
    "GA" => "Gabon",
    "GM" => "Gambia",
    "GE" => "Georgia",
    "DE" => "Germany",
    "GH" => "Ghana",
    "GI" => "Gibraltar",
    "GR" => "Greece",
    "GL" => "Greenland",
    "GD" => "Grenada",
    "GP" => "Guadeloupe",
    "GU" => "Guam",
    "GT" => "Guatemala",
    "GN" => "Guinea",
    "GW" => "Guinea-Bissau",
    "GY" => "Guyana",
    "HT" => "Haiti",
    "HM" => "Heard Island and Mcdonald Islands",
    "VA" => "Vatican City",
    "HN" => "Honduras",
    "HK" => "Hong Kong",
    "HU" => "Hungary",
    "IS" => "Iceland",
    "IN" => "India",
    "ID" => "Indonesia",
    "IR" => "Iran",
    "IQ" => "Iraq",
    "IE" => "Ireland",
    "IL" => "Israel",
    "IT" => "Italy",
    "JM" => "Jamaica",
    "JP" => "Japan",
    "JO" => "Jordan",
    "KZ" => "Kazakhstan",
    "KE" => "Kenya",
    "KI" => "Kiribati",
    "KP" => "North Korea",
    "KR" => "Korea",
    "KW" => "Kuwait",
    "KG" => "Kyrgyzstan",
    "LA" => "Laos",
    "LV" => "Latvia",
    "LB" => "Lebanon",
    "LS" => "Lesotho",
    "LR" => "Liberia",
    "LY" => "Libya",
    "LI" => "Liechtenstein",
    "LT" => "Lithuania",
    "LU" => "Luxembourg",
    "MO" => "Macao",
    "MK" => "Macedonia",
    "MG" => "Madagascar",
    "MW" => "Malawi",
    "MY" => "Malaysia",
    "MV" => "Maldives",
    "ML" => "Mali",
    "MT" => "Malta",
    "MH" => "Marshall Islands",
    "MQ" => "Martinique",
    "MR" => "Mauritania",
    "MU" => "Mauritius",
    "YT" => "Mayotte",
    "MX" => "Mexico",
    "FM" => "Micronesia",
    "MD" => "Moldova",
    "MC" => "Monaco",
    "MN" => "Mongolia",
    "MS" => "Montserrat",
    "MA" => "Morocco",
    "MZ" => "Mozambique",
    "MM" => "Myanmar",
    "NA" => "Namibia",
    "NR" => "Nauru",
    "NP" => "Nepal",
    "NL" => "Netherlands",
    "AN" => "Netherlands Antilles",
    "NC" => "New Caledonia",
    "NZ" => "New Zealand",
    "NI" => "Nicaragua",
    "NE" => "Niger",
    "NG" => "Nigeria",
    "NU" => "Niue",
    "NF" => "Norfolk Island",
    "MP" => "Northern Mariana Islands",
    "NO" => "Norway",
    "OM" => "Oman",
    "PK" => "Pakistan",
    "PW" => "Palau",
    "PS" => "Palestinian Territories",
    "PA" => "Panama",
    "PG" => "Papua New Guinea",
    "PY" => "Paraguay",
    "PE" => "Peru",
    "PH" => "Philippines",
    "PN" => "Pitcairn",
    "PL" => "Poland",
    "PT" => "Portugal",
    "PR" => "Puerto Rico",
    "QA" => "Qatar",
    "RE" => "Reunion",
    "RO" => "Romania",
    "RU" => "Russian Federation",
    "RW" => "Rwanda",
    "SH" => "Saint Helena",
    "KN" => "Saint Kitts and Nevis",
    "LC" => "Saint Lucia",
    "PM" => "Saint Pierre and Miquelon",
    "VC" => "Saint Vincent and the Grenadines",
    "WS" => "Samoa",
    "SM" => "San Marino",
    "ST" => "Sao Tome and Principe",
    "SA" => "Saudi Arabia",
    "SN" => "Senegal",
    "CS" => "Serbia and Montenegro",
    "SC" => "Seychelles",
    "SL" => "Sierra Leone",
    "SG" => "Singapore",
    "SK" => "Slovakia",
    "SI" => "Slovenia",
    "SB" => "Solomon Islands",
    "SO" => "Somalia",
    "ZA" => "South Africa",
    "GS" => "South Georgia and the South Sandwich Islands",
    "ES" => "Spain",
    "LK" => "Sri Lanka",
    "SD" => "Sudan",
    "SR" => "Suriname",
    "SJ" => "Svalbard and Jan Mayen",
    "SZ" => "Swaziland",
    "SE" => "Sweden",
    "CH" => "Switzerland",
    "SY" => "Syrian Arab Republic",
    "TW" => "Taiwan",
    "TJ" => "Tajikistan",
    "TZ" => "Tanzania",
    "TH" => "Thailand",
    "TL" => "Timor-Leste",
    "TG" => "Togo",
    "TK" => "Tokelau",
    "TO" => "Tonga",
    "TT" => "Trinidad and Tobago",
    "TN" => "Tunisia",
    "TR" => "Turkey",
    "TM" => "Turkmenistan",
    "TC" => "Turks and Caicos Islands",
    "TV" => "Tuvalu",
    "UG" => "Uganda",
    "UA" => "Ukraine",
    "AE" => "United Arab Emirates",
    "GB" => "United Kingdom",
    "US" => "United States",
    "UM" => "United States Minor Outlying Islands",
    "UY" => "Uruguay",
    "UZ" => "Uzbekistan",
    "VU" => "Vanuatu",
    "VE" => "Venezuela",
    "VN" => "Viet Nam",
    "VG" => "Virgin Islands, British",
    "VI" => "Virgin Islands, U.S.",
    "WF" => "Wallis and Futuna",
    "EH" => "Western Sahara",
    "YE" => "Yemen",
    "ZM" => "Zambia",
    "ZW" => "Zimbabwe"
];

function country_lock_activate()
{
    global $db, $countries;
    require_once MYBB_ROOT . "inc/adminfunctions_templates.php";

    $current_country_lock = ''; 
    $country_options = '';
    foreach ($countries as $code => $name) {
        $selected = ($code == $current_country_lock) ? ' selected' : '';
        $country_options .= '<option value="' . $code . '"' . $selected . '>' . $name . '</option>';
    }

    $template = '
<html>
<head>
<title>Country lock</title>
{$headerinclude}
<script type="text/javascript">
    function updateLink() {
        var select = document.getElementsByName("country_lock")[0];
        var selectedValue = select.options[select.selectedIndex].value;
        var link = document.getElementById("confirmlock");
        link.onclick = function() {
                countrylock(selectedValue);
        };
    }

    function countrylock(country) {
        $.ajax({
            type: "POST",
            url: "countrylock.php",
            dataType: "json",
            data: { country: country },
            success: function(response) {
                document.getElementById("currentlocked").innerHTML = "<strong>" + response.data + "</strong>";
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: " + status + " - " + error);
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
                                <strong>Country Lock</strong>
                            </td>
                        </tr>
                        <tr>
                            {$current_country_lock}
                        </tr>
                        <tr>
							<td align="center" class="trow1" colspan="5">
								<strong>Select lock:</strong>
									<select name="country_lock" onchange="updateLink()">
										' . $country_options . '
								</select>
                            </td>
                        </tr>
                    </tbody>
                </table>
				<br>
				<div align="center" bis_skin_checked="1">
					<input id="confirmlock" type="submit" onclick="countrylock(\'\');" class="button" value="Confirm">
				</div>
            </td>
        </tr>
    </tbody>
</table>
{$footer}
</body>
</html>';

    $insert_array = [
        'title' => 'usercp_country_lock',
        'template' => $db->escape_string($template),
        'sid' => '-1',
        'version' => '',
        'status' => '',
        'dateline' => TIME_NOW
    ];
    $db->insert_query('templates', $insert_array);
}

function country_lock_deactivate()
{
    global $db;
    require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
    
    $db->delete_query('templates', "title = 'usercp_country_lock'");
}

function country_lock_usercp()
{
    global $mybb, $db, $lang, $templates, $header, $footer, $headerinclude, $usercpnav, $current_country_lock, $countries;

    if ($mybb->input['action'] == "countrylock") {
        $user_lock = $mybb->user['country_lock'];
        if (!$user_lock || $user_lock === '') {
            $current_country_lock = '<td align="center" id="currentlocked" class="trow1"><strong>No current country lock selected</strong></td>';
        } else {
            $current_country_lock = '<td align="center" id="currentlocked" class="trow1"><strong>Current Lock: ' .  $user_lock . '</strong>';
        }
        eval("\$country_lock_page = \"" . $templates->get("usercp_country_lock") . "\";");
        output_page($country_lock_page);
    }
}

function country_lock_login_check()
{
    global $mybb, $db;

    if ($mybb->user['uid'] > 0) {
		$uid = (int)$mybb->user['uid'];
        $query = $db->simple_select('users', 'country_lock', "uid = '{$uid}'");
        $country_lock = $db->fetch_field($query, 'country_lock');
		
        if ($country_lock && $country_lock !== "") {
            
			if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
				$_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
			}
			$ip = $db->escape_binary(my_inet_pton(filter_input(INPUT_SERVER, 'REMOTE_ADDR')));
			
			// Local host
			if ($ip === '127.0.0.1' || $ip === '::1') {
                return; 
            }
			
			// Whois
			$cacert_path = MYBB_ROOT . "cacert.pem";
			$ch = curl_init('http://ipwhois.app/json/'.$ip); // todo: use pro ip-api api used in proxy_check.php
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CAINFO, $cacert_path);
			$json = curl_exec($ch);
			curl_close($ch);
			
			// CC
			$result = json_decode($json, true);
			$cc = $ipwhois_result['country_code'];		
			if ($country_lock != $cc) {
				redirect('login.php', 'Access restricted to specified country.');
			}
        }
    }
}

$plugins->add_hook('usercp_start', 'country_lock_usercp');
$plugins->add_hook('login_start', 'country_lock_login_check');
?>
