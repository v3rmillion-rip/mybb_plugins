<?php

if(!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

function user_info_api_info() {
    return [
        "name"          => "User Info API",
        "description"   => "An API to retrieve user information in JSON format for the Discord Bot",
        "website"       => "",
        "author"        => "JP (yakov)",
        "authorsite"    => "",
        "version"       => "1.0",
        "compatibility" => "18*"
    ];
}

function user_info_api_init() {
    global $mybb, $db;
    
    // Check if the request is for the API endpoint
    if($mybb->input['action'] === 'user_info') {
        header('Content-Type: application/json');
        
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        $auth_key = str_replace('Bearer ', '', $auth_header);
        
        // Password is compared with the sha-256 checksum of the github deploy password
        if(hash('sha256', $auth_key) !== "REDACTED") {
            echo json_encode(['error' => 'Invalid Authentication']);
            exit;
        }
        
        // Determine whether to use UID, Discord ID, username, or handle
        $uid = intval($mybb->input['uid'] ?? 0);
        $discord_id = $mybb->input['discord_id'] ?? '';
        $username = $mybb->input['username'] ?? '';
        $handle = $mybb->input['handle'] ?? '';
        
        if ($uid > 0) {
            // Fetch user by UID
            $user = get_user($uid);
        } elseif (!empty($username)) {
            // Fetch user by username
            $query = $db->simple_select('users', '*', "username='{$db->escape_string($username)}'");
            $user = $db->fetch_array($query);
        } elseif (!empty($discord_id)) {
            // Fetch user by Discord ID
            $query = $db->simple_select('users', '*', "discord_id='{$db->escape_string($discord_id)}'");
            $user = $db->fetch_array($query);
        } elseif (!empty($handle)) {
            // Fetch user by handle
            $query = $db->simple_select('userhandles', 'uid', "handle='{$db->escape_string($handle)}'");
            $handle_entry = $db->fetch_array($query);
            if ($handle_entry) {
                // Fetch user by UID obtained from handle
                $user = get_user($handle_entry['uid']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Handle not found!']);
                exit;
            }
        } else {
            echo json_encode(['error' => 'Invalid UID, Discord ID, or username']);
            exit;
        }
        
        if(!$user) {
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        
        global $cache;
        $groupscache = $cache->read('usergroups');
        $current_gid = $user['usergroup'];
        preg_match('/color:\s*#([A-Fa-f0-9]{6})/', $groupscache[$current_gid]['namestyle'], $matches);
        $ug_color = "#".$matches[1] ?? '000000';
        
        $response = [
            'uid'        => $user['uid'],
            'username'   => strval($user['username']),
            'avatar'     => $user['avatar'],
            'ug_name'    => $groupscache[$current_gid]['title'],
            'ug_color'   => $ug_color,
            'email'      => strval($user['email']),
            'reputation' => $user['reputation'],
            'threads'    => $user['threadnum'],
            'posts'      => $user['postnum'],
            'regdate'    => $user['regdate'],
            'lastactive' => $user['lastactive'],
            'timeonline' => $user['timeonline'],
            // 'regip'      => strval($user['regip']),
            // 'lastip'     => strval($user['lastip']),           
            'discord_id' => $user['discord_id'],
            'usernotes'  => $user['usernotes']
        ];
        
        echo json_encode($response);
        exit;
    }
}

$plugins->add_hook('misc_start', 'user_info_api_init');