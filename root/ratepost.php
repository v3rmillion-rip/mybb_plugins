<?php

define('IN_MYBB', 1);
define("NO_ONLINE", 1);
require_once './global.php';

// Check if the user is logged in
$uid = (int) $mybb->user['uid'];
if (!$uid || $uid & $uid == 0) {
    die(json_encode(array('error' => 'User not logged in')));
}

// Data
$pid = (int) $mybb->input['pid'];
$rating = (int) $mybb->input['rating'];
$action = $mybb->input['action'];
$user_rated = 0;

$query = $db->simple_select("posts", "uid", "pid = {$pid}");
$author_uid = (int)$db->fetch_field($query, "uid");
if (!$author_uid) {
	die(json_encode(array('error' => 'Post not found')));
}

// Not rating 
if ($action !== 'rate') {
	
	// Find user rated 
	$pid_query = $db->simple_select('user_post_ratings', 'rating', "uid = '{$uid}' AND pid = '{$pid}'");
    $user_rated = $db->fetch_field($pid_query, 'rating');
	
	// Likes and dislikes
	$likes_query = $db->simple_select('post_ratings', 'SUM(num_likes) as likes', "pid = '{$pid}'");
	$likes = (int) $db->fetch_field($likes_query, 'likes');
	
	$dislikes_query = $db->simple_select('post_ratings', 'SUM(num_dislikes) as dislikes', "pid = '{$pid}'");
	$dislikes = (int) $db->fetch_field($dislikes_query, 'dislikes');
	
	echo json_encode(array(
		'likes' => $likes,
		'dislikes' => $dislikes,
		'user_rated' => $user_rated
	));
	exit;
}
if ($author_uid === $uid) {
	die(json_encode(array('error' => 'You cannot rate your own post.')));	
}

// Validate rating value (1 for like, -1 for dislike)
if ($rating !== 1 && $rating !== -1) {
    die(json_encode(array('error' => 'Invalid rating')));
}

// Check if user has already rated this post
$time = time(); 
$pid_set = false;
$pid_swapped = false;
$pid_query = $db->simple_select('user_post_ratings', '*', "uid = '{$uid}'");
$rating_deleted = false;
while ($user_rating = $db->fetch_array($pid_query)) {
    if ($user_rating['pid'] == $pid) {
		
		$time_left = floor((300 - ($time - $user_rating['timestamp'])) / 60);
		if ($time_left > 0) {
			 die(json_encode(array('error' => "You must wait " . $time_left . " minutes to rate again.")));
		}
        if ($user_rating['rating'] == 1) {
            if ($rating === -1) { // If dislike then add disliked
                 $db->write_query("UPDATE " . TABLE_PREFIX . "user_post_ratings SET rating = -1, timestamp = " . (int)$time . " WHERE uid = " . (int)$uid . " AND pid = " . (int)$pid);
                $pid_swapped = true;
            } else {
                $db->delete_query('user_post_ratings', "uid = '{$uid}' AND pid = '{$pid}'");
				$rating_deleted = true;
            }
        } elseif ($user_rating['rating'] == -1) {
            if ($rating === 1) { // If liked, then change to disliked
                $db->write_query("UPDATE " . TABLE_PREFIX . "user_post_ratings SET rating = 1, timestamp = " . (int)$time . " WHERE uid = " . (int)$uid . " AND pid = " . (int)$pid);
                $pid_swapped = true;
            } else {
                $db->delete_query('user_post_ratings', "uid = '{$uid}' AND pid = '{$pid}'");
				$rating_deleted = true;
            }
        }
        $pid_set = true;
		break;
    }
}
if (!$pid_set) {
    $insert_array = array(
        'uid' => $uid,
        'rating' => $rating,
        'pid' =>  $pid,
		'timestamp' => (int)$time
    );
    $db->insert_query('user_post_ratings', $insert_array);
}

// Update post ratings
$rating_query = $db->simple_select('post_ratings', '*', "pid = '{$pid}'");
$existing_rating = $db->fetch_array($rating_query);
if (!$existing_rating) {
    $insert_array = array(
        'pid' => $pid,
        'num_likes' => ($rating === 1) ? 1 : 0,
        'num_dislikes' => ($rating === -1) ? 1 : 0,
		'author_uid' => $author_uid
    );
    $db->insert_query('post_ratings', $insert_array);
	$user_rated = $rating;
} else {
    // Update existing post ratings based on user action
    if ($rating === 1) {
        if ($pid_set) { // User already rated        
            if ($pid_swapped) { // Swap dislike for like
                $db->write_query("UPDATE " . TABLE_PREFIX . "post_ratings SET num_dislikes = num_dislikes - 1 WHERE pid = '{$pid}'");
				$db->write_query("UPDATE " . TABLE_PREFIX . "post_ratings SET num_likes = num_likes + 1 WHERE pid = '{$pid}'");
				$user_rated = 1;
            } else { // Deselect like
				 $db->write_query("UPDATE " . TABLE_PREFIX . "post_ratings SET num_likes = num_likes - 1 WHERE pid = '{$pid}'"); 
			}
        } else { // Normal
            $db->write_query("UPDATE " . TABLE_PREFIX . "post_ratings SET num_likes = num_likes + 1 WHERE pid = '{$pid}'"); 
			$user_rated = 1;
        }
    } else { 
        if ($pid_set) { // User already rated
            if ($pid_swapped) { // Swap dislike for like
                $db->write_query("UPDATE " . TABLE_PREFIX . "post_ratings SET num_likes = num_likes - 1 WHERE pid = '{$pid}'");
				$db->write_query("UPDATE " . TABLE_PREFIX . "post_ratings SET num_dislikes = num_dislikes + 1 WHERE pid = '{$pid}'");
				$user_rated = -1;
            } else { // Deselect like
				 $db->write_query("UPDATE " . TABLE_PREFIX . "post_ratings SET num_dislikes = num_dislikes - 1 WHERE pid = '{$pid}'"); 
			}
        } else { // Normal
            $db->write_query("UPDATE " . TABLE_PREFIX . "post_ratings SET num_dislikes = num_dislikes + 1 WHERE pid = '{$pid}'");
			$user_rated = -1;
        }
    }
}

// Get updated like and dislike counts
$likes_query = $db->simple_select('post_ratings', 'SUM(num_likes) as likes', "pid = '{$pid}'");
$likes = (int) $db->fetch_field($likes_query, 'likes');

$dislikes_query = $db->simple_select('post_ratings', 'SUM(num_dislikes) as dislikes', "pid = '{$pid}'");
$dislikes = (int) $db->fetch_field($dislikes_query, 'dislikes');


if(is_object($plugins)) {
	$arrr = array(
		'user_rated' => $user_rated,
		'author_uid' => $author_uid,
		'pid' => $pid
	);
	$plugins->run_hooks('ratepost_end', $arrr);
}
echo json_encode(array(
    'likes' => $likes,
    'dislikes' => $dislikes,
	'user_rated' => $user_rated
));
exit;
?>