<?php

if (!defined("IN_MYBB")) {
    die("Direct access not allowed.");
}

function robots_info()
{
    return array(
        "name" => "Robots",
        "description" => "SEO Crawler detection",
        "website" => "",
        "author" => "JP (yakov)",
        "authorsite" => "",
        "version" => "1.0",
        "compatibility" => "18*"
    );
}

function robots_install() {}

function robots_uninstall() {}

function robots_activate() {}

function robots_deactivate() {}

function is_bot($ip_address)
{
    $hostname = gethostbyaddr($ip_address);
    
    if (preg_match('/\.google(bot)?\.com$/', $hostname) || preg_match('/\.search\.msn\.com$/', $hostname)) {
        $resolved_ip = gethostbyname($hostname);
        if ($ip_address === $resolved_ip) {
            return true;
        }
    }
    return false;
}

function robots_detect()
{
    global $db, $mybb, $session, $groupscache, $meta_tags;
    
    $isRobot = false;
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
    $auth_key = str_replace('Bearer ', '', $auth_header);
    
    // Password is compared with the sha-256 checksum of the github deploy password
    if(!empty($auth_header) && hash('sha256', $auth_key) === "REDACTED") {
        $mybb->usergroup = $groupscache[22]; // Bot usergroup
        $isRobot = true;
    }
    
    $google_bots = array(
        'Googlebot',
        'Googlebot-Image',
        'Googlebot-News',
        'Googlebot-Video',
        'Bingbot'
    );
    
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
    
    foreach ($google_bots as $bot) {
        if (stripos($user_agent, $bot) !== false && is_bot($ip_address)) {
            $mybb->usergroup = $groupscache[22];
            $isRobot = true;
            break;
        }
    }
    
    $meta_tags = '';
    $metaSiteName = "<meta property=\"og:site_name\" content=\"V3rmillion\" />";
    $metaType = "<meta property=\"og:type\" content=\"website\" />";
    $metaTitle = "<meta property=\"og:title\" content=\"V3rmillion\"/>";
    $metaThemeColor = "<meta name=\"theme-color\" content=\"#CD1818\" data-react-helmet=\"true\" />";

    $urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    if ($urlPath == "/showthread.php") {
        $thread = get_thread($mybb->get_input('tid', MyBB::INPUT_INT));
        
        if ($thread && $thread['visible'] == 1) {
            $firstPost = get_post($thread['firstpost']);
            $parser_options = array(
                'allow_html' => 0,
                'allow_mycode' => 1,
                'allow_smilies' => 0,
                'allow_imgcode' => 0,
                'allow_videocode' => 0,
                'filter_badwords' => 1
            );
            
            require_once MYBB_ROOT."inc/class_parser.php";
            $parser = new postParser;
            $firstPostDescription = my_strip_tags($parser->parse_message($firstPost['message'], $parser_options));
            $firstPostDescription = preg_replace("/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|(([^\s()<>]+|(([^\s()<>]+)))*))+(?:(([^\s()<>]+|(([^\s()<>]+)))*)|[^\s`!()[]{};:'\".,<>?«»“”‘’]))/", '(link)', $firstPostDescription);
            
            if (strlen($firstPostDescription) > 170) {
                $firstPostDescription = substr($firstPostDescription, 0, 170) . "...";
            }
            
            $meta_tags .= "<meta property=\"og:site_name\" content=\"V3rmillion - Thread by " . $thread['username'] . "\" />";
            $meta_tags .= "\n" . $metaType;
            $meta_tags .= "\n<meta property=\"og:title\" content=\"" . $thread['subject'] . "\"/>";
            $meta_tags .= "\n<meta property=\"og:description\" content=\"" . $firstPostDescription . "\"/>";
            $meta_tags .= "\n" . $metaThemeColor;
        } else {
            $meta_tags .= $metaSiteName . "\n" . $metaType . "\n" . $metaTitle . "\n" . $metaThemeColor;
        }
    } else if ($urlPath == "/member.php" && $mybb->get_input('action') == "profile") {
        $uid = $mybb->get_input('uid', MyBB::INPUT_INT);
        if ($uid) {
            $memprofile = get_user($uid);
            if ($memprofile)
			{
				if(!$memprofile['displaygroup'])
				{
					$memprofile['displaygroup'] = $memprofile['usergroup'];
				}
			
				$displaygroup = usergroup_displaygroup($memprofile['displaygroup']);
			
				// Regular expression to match the color value
				preg_match('/color:\s*#([A-Fa-f0-9]{6})/', $displaygroup['namestyle'], $matches);
			
				$meta_tags .= $metaSiteName . "\n" . $metaType;
				$meta_tags .= "\n<meta property=\"og:title\" content=\"Profile of " . $memprofile['username'] . "\"/>";
			
				// Check if a match was found and output the color value
				if (isset($matches[1])) {
					$color = $matches[1];
					$meta_tags .= "\n<meta name=\"theme-color\" content=\"#" . $color . "\" data-react-helmet=\"true\" />";
				
					$query = $db->simple_select("userfields", "fid2", "ufid = '{$uid}'");
					
					if($db->num_rows($query) > 0)
					{
						$user_fields = $db->fetch_array($query);
					
						if (!empty($user_fields['fid2']))
						{
							$meta_tags .= "\n<meta property=\"og:description\" content=\"" . $user_fields['fid2'] . "\" />";
						}
					}
				}
			} else {
                $meta_tags .= $metaSiteName . "\n" . $metaType . "\n" . $metaTitle . "\n" . $metaThemeColor;
            }
        } else {
            $meta_tags .= $metaSiteName . "\n" . $metaType . "\n" . $metaTitle . "\n" . $metaThemeColor;
        }
    } else {
        $meta_tags .= $metaSiteName . "\n" . $metaType . "\n" . $metaTitle . "\n" . $metaThemeColor;
    }

    $headerinclude .= $meta_tags;
}

function  robots_addmeta() {
    global $meta_tags, $headerinclude;
    if ($meta_tags) $headerinclude = $meta_tags . $headerinclude;
}

$plugins->add_hook("global_start", "robots_detect");
$plugins->add_hook("global_end", "robots_addmeta");

?>
