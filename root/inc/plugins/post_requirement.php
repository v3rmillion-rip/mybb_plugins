<?php
if(!defined('IN_MYBB')) {
    die('Direct access to this file is not allowed.');
}

function post_requirement_info() {
    return array(
        "name"          => "Post Requirement",
        "description"   => "Adds a post requirement to post in a certain subforum.",
        "website"       => "",
        "author"        => "Mellon",
        "authorsite"    => "",
        "version"       => "1.0",
        "guid"          => "",
        "compatibility" => "18*"
    );
}

function post_requirement_activate() {
    global $db;

    // Setting group
    $setting_group = [
        'name' => 'post_requirement',
        'title' => 'Post Requirement Settings',
        'description' => 'Settings for the Post Requirement plugin.',
        'disporder' => 1,
        'isdefault' => 0
    ];
    $db->insert_query('settinggroups', $setting_group);
    $gid = $db->insert_id();

    // Settings
    $setting = [
        'name' => 'post_requirement_enabled',
        'title' => 'Enable Post Requirement',
        'description' => 'Do you want to enable the post requirement?',
        'optionscode' => 'yesno',
        'value' => '0',
        'disporder' => 1,
        'gid' => $gid
    ];
    $db->insert_query('settings', $setting);

    $setting = [
        'name' => 'post_requirement_post_count',
        'title' => 'Required Post Count',
        'description' => 'The number of posts a user needs to have before they can post in the subforum.',
        'optionscode' => 'text',
        'value' => '0',
        'disporder' => 2,
        'gid' => $gid
    ];
    $db->insert_query('settings', $setting);

    $setting = [
        'name' => 'post_requirement_forum_ids',
        'title' => 'Forum IDs',
        'description' => 'Comma-separated list of forum IDs where the post requirement is applied.',
        'optionscode' => 'text',
        'value' => '',
        'disporder' => 3,
        'gid' => $gid
    ];
    $db->insert_query('settings', $setting);


    rebuild_settings();
}

function post_requirement_deactivate() {
    global $db;

    $db->delete_query('settings', "name IN ('post_requirement_enabled', 'post_requirement_post_count', 'post_requirement_forum_ids')");
    $db->delete_query('settinggroups', "name = 'post_requirement'");

    rebuild_settings();
}

function post_requirement_is_installed() {
    global $db;

    return $db->num_rows($db->simple_select('settinggroups', 'gid', "name = 'post_requirement'"));
}

function daysleft($ts, $days) {
	
    $date = new DateTime();
    $date->setTimestamp($ts);

    $curr = new DateTime();
    $diff = $curr->diff($date)->days;
	
	if ($days < $diff) {
		return 0;
	}
    return max(0, ($days - $diff));
}

function post_requirement_check() {
    global $mybb, $db, $pm_warning;

    if(!$mybb->user['uid'] || !$mybb->settings['post_requirement_enabled']) {
        return;
    }

    $required_posts = (int)$mybb->settings['post_requirement_post_count'];
    $forum_ids = array_map('trim', explode(',', $mybb->settings['post_requirement_forum_ids']));
	$allowed_usergroups = [2, 1, 5, 7];
	$date_delta = daysleft($mybb->user['regdate'], 14);
    if(in_array($mybb->user['usergroup'], $allowed_usergroups) && ($mybb->user['postnum'] < $required_posts || $date_delta > 0)) {
	
	   $more_posts = ($mybb->user['postnum'] < $required_posts) ? $required_posts - $mybb->user['postnum'] : 0;
	   
		// Get fid
	   $fid = 0;
	   if (isset($mybb->input['fid'])) {
			$fid = (int)$mybb->input['fid'];
	   } else if (isset($mybb->input['tid'])) {
			$tid = (int)$mybb->input['tid'];
			$query = $db->simple_select('threads', 'fid', "tid={$tid}");
			$fid = $db->fetch_field($query, 'fid');
	   }
	   
      if(in_array($fid, $forum_ids)) {
        $pm_warning .= '
			 <div class="postamt_alert" bis_skin_checked="1">
				<span>You need </span>
				<strong>' . $more_posts . '</strong>
				<span> more </span>
				<a href="rules.php" style="color:#cd1818;">rule following </a> 
				<span> posts in </span>
				<a href="index.php" style="color:#cd1818;">other sections</a> 
				<span> and </span>
				<strong>'. $date_delta .'</strong>
				<span> more days since registration to post in this section. Alternatively, you can </span>
				<a href="autoupgrade.php" style="color:#cd1818;">get a membership</a> 
				<span> with us to post here right away.</span>
			</div>
			<br>';
		}
    }
}
$plugins->add_hook('global_start', 'post_requirement_check');

function post_requirement_post_validate($post_this) {
    global $mybb, $db;

    if (!$mybb->settings['post_requirement_enabled']) {
        return;
    }
    
	$post = &$post_this->data;
    $required_posts = (int)$mybb->settings['post_requirement_post_count'];
    $forum_ids = array_map('trim', explode(',', $mybb->settings['post_requirement_forum_ids']));
    $allowed_usergroups = [1, 2, 5, 7];
    $regdate = $mybb->user['regdate'];

    $date_delta = daysleft($regdate, 14);
    if (!in_array($mybb->user['usergroup'], $allowed_usergroups)) {
        return;
    }
    
    if ($mybb->user['postnum'] < $required_posts || $date_delta > 0) {
        $more_posts = $required_posts - $mybb->user['postnum'];
        $fid = (int)$post['fid'];
        
        if (in_array($fid, $forum_ids)) {
            $post_this->set_error('You need ' . $more_posts . ' more posts and ' . $date_delta . ' more days since registration to post in this section. Alternatively, you can get a membership with us to post here right away.');
        }
    }
}
$plugins->add_hook('datahandler_post_validate_post', 'post_requirement_post_validate');
$plugins->add_hook('datahandler_post_validate_thread', 'post_requirement_post_validate');