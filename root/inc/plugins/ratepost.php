<?php

if (!defined('IN_MYBB')) {
    die('This file cannot be accessed directly.');
}

function ratepost_info()
{
    return array(
        "name"          => "V3rmillion ratepost",
        "description"   => "Ratepost from v3rmillion.",
        "website"       => "",
        "author"        => "JP and Mellon",
        "authorsite"    => "",
        "version"       => "1.0",
        "guid"          => "",
        "compatibility" => "18*"
    );
}

function ratepost_install()
{
    global $db;

    if (!$db->table_exists('post_ratings')) {
        $db->write_query("
            CREATE TABLE `" . TABLE_PREFIX . "post_ratings` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `pid` int(10) unsigned NOT NULL,
				`author_uid` int(10) unsigned NOT NULL,
                `num_likes` int(10) unsigned NOT NULL DEFAULT '0',
                `num_dislikes` int(10) unsigned NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `pid` (`pid`),
				KEY `author_uid` (`author_uid`),
				UNIQUE KEY `unique_pid_author` (`pid`, `author_uid`)
            ) ENGINE=InnoDB " . $db->build_create_table_collation() . ";
        ");
		
		$db->write_query("
            CREATE TABLE `" . TABLE_PREFIX . "user_post_ratings` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`uid` int(10) unsigned NOT NULL,
                `rating` int(10) signed NOT NULL DEFAULT '0',
				`pid` int(10) unsigned NOT NULL DEFAULT '0',
				`timestamp` int(10) unsigned NOT NULL,
                PRIMARY KEY (`id`),
                KEY `uid` (`uid`),
				KEY `pid` (`pid`)
            ) ENGINE=InnoDB " . $db->build_create_table_collation() . ";
        ");
    }
}

function ratepost_is_installed()
{
    global $db;

    return $db->table_exists('post_ratings') && $db->table_exists('user_post_ratings');
}

function ratepost_uninstall()
{
    global $db;
}

function ratepost_activate() {
	global $db;

	// Rating page 
 	$template_content = '<html>
 <head>
 <title>{$mybb->settings[\'bbname\']} - Post Ratings</title>
 {$headerinclude}
 </head>
 <body>
 {$header}
 {$multipage}
 <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
     <tr>
         <td class="thead" colspan="{$colspan}"><strong>Viewing post ratings</strong></td>
     </tr>
     <tr>
         <td class="tcat" align="left"><span class="smalltext"><strong>Username</strong></span></td>
         <td class="tcat" width="40%" align="center"><span class="smalltext"><strong>Rating</strong></span></td>
         <td class="tcat" width="40%" align="center"><span class="smalltext"><strong>Date</strong></span></td>
     </tr>
 	<tr>
 		{$ratings}
     </tr>
 </table>
 {$footer}
 </body>
 </html>';
     $template_array = array(
         'title'     => 'misc_viewratings',
         'template'  => $db->escape_string($template_content),
         'sid'       => '-1',
         'version'   => '',
         'dateline'  => TIME_NOW
     );
     $db->insert_query("templates", $template_array);
	
	if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();
        if ($alertTypeManager === null) {
            $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
        }    
        if ($alertTypeManager) {
            $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
            $alertType->setCode('ratealert');
            $alertType->setEnabled(true);
            $alertType->setCanBeUserDisabled(true);
			$alertType->setDefaultUserEnabled(true);
            $alertTypeManager->add($alertType);
        }
    }
}

function ratepost_deactivate()
{
	global $db;
}

$plugins->add_hook('postbit', 'ratepost_postbit');
function ratepost_postbit(&$post)
{ 
    global $db, $mybb, $lang, $templates;

    $pid = (int)$post['pid'];
	
	// Likes
	$likes_query = $db->simple_select('post_ratings', 'SUM(num_likes) as likes', "pid = '{$pid}'");
	$qlikes = (int)$db->fetch_field($likes_query, 'likes');
	$likes = ($qlikes !== null) ? (int)$qlikes : 0;
	
	// Dislikes
	$dislikes_query = $db->simple_select('post_ratings', 'SUM(num_dislikes) as dislikes', "pid = '{$pid}'");
	$qdislikes = (int)$db->fetch_field($dislikes_query, 'dislikes');
	$dislikes = ($qdislikes !== null) ? (int)$qdislikes : 0;
	
    $post['ratepost'] = '<div id="like_buttons' . $pid . '" style="display:inline-block;"">
        <div style="margin-right: 5px; background-color: #202020; display:inline-block; min-width:40px; border-radius: 3px; text-align:center; padding: 1px 5px 1px 5px;">
            <a href="#likepid' . $pid . '" id="upboat' . $pid . '" onclick="ratepost(' . $pid . ', \'1\', \'rate\')">
                <i style="color:#29CB09;" id="upboat' . $pid . ' i" class="fa fa-thumbs-o-up" aria-hidden="true"></i>
            </a>
            <span style="font-weight:700;" id="upcount' . $pid . '"> ' . $likes . ' </span>
        </div>
        <div style="margin-right:5px; background-color: #202020; display:inline-block; min-width:40px; border-radius: 3px; text-align:center; padding: 1px 5px 1px 5px;">
            <a href="#dislikepid' . $pid . '" id="downboat' . $pid . '" onclick="ratepost(' . $pid . ', \'-1\', \'rate\')">
                <i style="color:#B40404" id="downboat' . $pid . ' i" class="fa fa-thumbs-o-down" aria-hidden="true"></i>
            </a>
            <span style="font-weight:700;" id="downcount' . $pid . '"> ' . $dislikes . ' </span>
        </div>
        <div style="background-color: #202020; display:inline-block; border-radius: 3px; text-align:center; padding: 1px 5px 1px 5px;">
            <a href="/misc.php?action=viewratings&pid=' . $pid . '">
                <i style="color:#a0a0a0; line-height: 22px;" class="fa fa-search" aria-hidden="true"></i>
            </a>
        </div>
    </div>
	<script> document.addEventListener("DOMContentLoaded", function() {ratepost(' . $pid . ', \'0\', \'get\');}); </script>';
}


$plugins->add_hook("misc_start", "ratepost_run");
function ratepost_run()
{
		global $mybb, $db, $lang, $templates, $theme, $headerinclude, $header, $footer, $multipage;
		
		if($mybb->input['action'] == "viewratings") {
		
			$pid = (int)$mybb->get_input('pid', MyBB::INPUT_INT);
			$colspan = 5;		
			$rating_count = 0;
			$ratings = '';
			$unknown_likes = 0;
			$unknown_dislikes = 0;
			
			// There are no ratings at this time.
			$pid_query = $db->simple_select('user_post_ratings', '*', "pid = '{$pid}'");
			while ($user_rating = $db->fetch_array($pid_query))
			{
				$rating_uid = (int)$user_rating['uid'];
				$rating_count = (int)$user_rating['rating'];
				if ($rating_uid == 0)
				{
					// These are unknown ratings which were scraped from before.
					// While unknown ratings do additionally appear on the post authors profile, they're not associated to the specific post and not either associated with the rating author as we do not have that data.
					// The only we data we have is the flat likes and dislikes of a post and of a user, no timestamps or authors.

					
					if ($rating_count >= 1) $unknown_likes = $rating_count;
					else $unknown_dislikes = abs($rating_count);

					continue;
				}

				$rating = ($rating_count === 1) ? "<span style=\"color:#29CB09;\">Liked</span>" : "<span style=\"color:#B40404;\">Disliked</span>";
				$timestamp = my_date('relative', $user_rating['timestamp']);
				$memprofile = get_user($rating_uid);
				$formattedname = format_name($memprofile['username'], $memprofile['usergroup'], $memprofile['displaygroup']);
			     
				$ratings .= "<tr>
					<td align=\"left\"><a class=\"largetext\" href=\"/member.php?action=profile&amp;uid={$rating_uid}\" bis_skin_checked=\"1\">{$formattedname}</a></td>
					<td align=\"center\">{$rating}</td>
					<td align=\"center\"><span>{$timestamp}</span></td>
				</tr>";		
				$rating_count++;
			}
			
			if ($unknown_likes > 0 || $unknown_dislikes > 0)
			{
				// We want to combine them into a single entry

				$rating = ($unknown_likes > 0 ? "<span style=\"color:#29CB09;\">{$unknown_likes} Liked</span>" : "") . 
          			($unknown_likes > 0 && $unknown_dislikes > 0 ? " and " : "") .
          			($unknown_dislikes > 0 ? "<span style=\"color:#B40404;\">{$unknown_dislikes} Disliked</span>" : "");

				$ratings .= "<tr>
					<td align=\"left\">Older ratings</td>
					<td align=\"center\">{$rating}</td>
					<td align=\"center\"><span>Ratings from before " . my_date($mybb->settings['dateformat'], 1704067200) . " do not have timestamps.</span></td>
				</tr>";
			} else if ($rating_count === 0) {
				$ratings .= "<tr>
					<td align=\"left\"><span>There are no ratings at this time.</span></td>
				</tr>";
			}
	
			eval("\$viewratings = \"".$templates->get("misc_viewratings")."\";");
			output_page($viewratings);
		}
}

function ratepost_ratingbar($uid) {
    global $mybb, $memprofile, $db, $ratingbar;

	if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1) {
        return;
    }

	$memuid = (int)$memprofile['uid'];
	$uid = (int)$mybb->user['uid'];
	
    // Likes, dislikes
	$likes_query = $db->simple_select('post_ratings', 'SUM(num_likes) as likes', "author_uid = '{$memuid}'");
	$likes = (int) $db->fetch_field($likes_query, 'likes');
	
	$dislikes_query = $db->simple_select('post_ratings', 'SUM(num_dislikes) as dislikes', "author_uid = '{$memuid}'");
	$dislikes = (int) $db->fetch_field($dislikes_query, 'dislikes');
	
    $rating_total = $likes + $dislikes;
	if ($rating_total > 0 && ($rating_total >= 5 || $memuid == $uid || $mybb->usergroup['canmodcp'])) {
			
			$like_percentage = ($likes / $rating_total) * 100;
			$dislike_percentage = ($dislikes / $rating_total) * 100;
				
			$ratingbar = '
		<tr>
			<td class="trow2">
				<strong>Rating:</strong>
			</td>
			<td class="trow2">
				<div id="rating_result_wrapper" style="height:10px;">
					<div class="likebar" id="positive" style="background-color:green; width:'.$like_percentage.'%; height:100%; float:left;">
						<span class="tooltiptext" style="color:green;">'.$likes.' like(s)</span>
					</div>
					<div class="likebar" id="negative" style="background-color:red; width:'.$dislike_percentage.'%; height:100%; float:left;">
						<span class="tooltiptext" style="color:red;">'.$dislikes.' dislike(s)</span>
					</div>
				</div>
			</td>
		</tr>';

	}
}

$plugins->add_hook('member_profile_end', 'ratepost_ratingbar');


function ratepost_ratealert($args) {
    global $mybb, $db;

    // Default myalerts setup
    if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1) {
        return;
    }

    $recep_uid = (int)$args['author_uid'];
    $pid = (int)$args['pid'];
	$uid = (int)$mybb->user['uid'];
	$user = get_user($uid);
	$formattedname = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	
	// Mods
	if ($mybb->usergroup['canmodcp']) {
		$query = $db->simple_select('ougc_awards_users', '*', "uid='{$recep_uid}' AND aid=7");
		if (!$db->fetch_array($query)) {
			$db->insert_query('ougc_awards_users', [
				'uid' => $recep_uid,
				'oid' => 1,
				'aid' => 7,
				'rid' => 0,
				'tid' => 0,
				'thread' => 0,
				'reason' => 'One of your posts was liked by <a href="member.php?action=profile&amp;uid=' . $uid . '" bis_skin_checked="1"><strong>'. $formattedname . '</strong></a>',
				'date' => time(),
				'disporder' => 0,
				'visible' => 1
			]);
		}
	}
	
	// Amount of Likes 
	$likes_query = $db->simple_select('post_ratings', 'SUM(num_likes) as likes', "author_uid = '{$recep_uid}'");
	$likes = (int)$db->fetch_field($likes_query, 'likes');
	if ($likes >= 500) {
		
		$query = $db->simple_select('ougc_awards_users', '*', "uid='{$recep_uid}' AND aid=8");
		if (!$db->fetch_array($query)) {
			$db->insert_query('ougc_awards_users', [
				'uid' => $recep_uid,
				'oid' => 1,
				'aid' => 8,
				'rid' => 0,
				'tid' => 0,
				'thread' => 0,
				'reason' => 'Achieving 500 likes',
				'date' => time(),
				'disporder' => 0,
				'visible' => 1
			]);
		}
	}
	
    myalerts_create_instances();
    $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();
    
    if (is_null($alertTypeManager) || $alertTypeManager === false) {
        global $cache;
        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
    }
    $alertType = $alertTypeManager->getByCode('ratealert');

    if ($alertType != null && $alertType->getEnabled()) {
        if (myalerts_can_view_thread(2, $recep_uid, $recep_uid)) {
			
            $alert = new MybbStuff_MyAlerts_Entity_Alert(
                $recep_uid,
                $alertType,
                $pid
            );
            $alert->setExtraDetails(
                array(
                    'pid'      => $pid,
                    'uid_name' => $formattedname
                )
            );
    
            $alertManager = MybbStuff_MyAlerts_AlertManager::getInstance();
    
            if (is_null($alertManager) || $alertManager === false) {
                global $cache;
                $alertManager = MybbStuff_MyAlerts_AlertManager::createInstance($mybb, $db, $cache, $plugins, $alertTypeManager);
            }
    
            $alertManager->addAlert($alert);
        }
    }
}
$plugins->add_hook('ratepost_end', 'ratepost_ratealert');



function start()
{
	global $mybb, $lang;
	
	if (class_exists('MybbStuff_MyAlerts_Formatter_AbstractFormatter') &&
	    class_exists('MybbStuff_MyAlerts_AlertFormatterManager') &&
	    !class_exists('MybbStuff_MyAlerts_Formatter_RatepostFormatter')
	) {
		
		class MybbStuff_MyAlerts_Formatter_RatepostFormatter
			extends MybbStuff_MyAlerts_Formatter_AbstractFormatter {
		
			private $parser;
		
			public function formatAlert(
				MybbStuff_MyAlerts_Entity_Alert $alert,
				array $outputAlert
			) {
				$alertContent = $alert->getExtraDetails();
				$threadLink = $this->buildShowLink($alert);
		
				return $this->lang->sprintf(
					'Liked by <strong>{1}</strong> for one of your posts.',
					$alertContent['uid_name']
				);
			}
		
			public function init() {
				if (!$this->lang->myalerts) {
					$this->lang->load('myalerts');
				}
		
				require_once MYBB_ROOT . 'inc/class_parser.php';
				$this->parser = new postParser;
			}
		
			public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert) {
				
				$alertContent = $alert->getExtraDetails();
				$threadLink = $this->mybb->settings['bburl'] . '/showthread.php?pid=' . (int)$alertContent['pid'] . '#pid' . (int)$alertContent['pid'];
				return $threadLink;
			}
		}
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();
		if (!$formatterManager) {
		    $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}
		if ($formatterManager) {
			$formatterManager->registerFormatter(new MybbStuff_MyAlerts_Formatter_RatepostFormatter($mybb, $lang, 'ratealert'));
		}
	}
	
}
$plugins->add_hook('myalerts_register_client_alert_formatters', 'start');