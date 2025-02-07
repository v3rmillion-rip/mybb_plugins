<?php
if (!defined("IN_MYBB")) {
    die("Direct access not allowed.");
}

function userhandles_info()
{
    return [
        "name" => "User Handles",
        "description" => "Adds custom handle support",
        "website" => "",
        "author" => "JP (yakov)",
        "authorsite" => "",
        "version" => "1.0",
        "guid" => "",
        "compatibility" => "18*",
    ];
}

function userhandles_install()
{
    global $db;

    if (!$db->table_exists("userhandles")) {
        $db->query("
            CREATE TABLE `" . TABLE_PREFIX . "userhandles` (
                `uid` INT(10) NOT NULL,
                `handle` VARCHAR(100) NOT NULL,
                `timestamp` BIGINT(20) NOT NULL,
                `hidden` TINYINT(1) NOT NULL DEFAULT 0,
                INDEX `uid_idx` (`uid`),
                UNIQUE KEY `unique_handle` (`handle`)
            ) ENGINE=MyISAM;
        ");
    }
}

function userhandles_is_installed()
{
    global $db;
    return $db->table_exists("userhandles");
}

function userhandles_activate()
{
    global $db;

    // Add a setting for admins to control handles
    $setting_group = [
        "name" => "userhandles",
        "title" => "User Handles Settings",
        "description" => "Settings for the User Handles plugin.",
        "disporder" => 1,
        "isdefault" => 0,
    ];
    $gid = $db->insert_query("settinggroups", $setting_group);

    $settings = [
        [
            "name" => "enable_userhandles",
            "title" => "Enable User Handles",
            "description" => "Enable or disable the user handles feature.",
            "optionscode" => "yesno",
            "value" => 1,
            "disporder" => 1,
            "gid" => $gid,
        ]
    ];

    foreach ($settings as $setting) {
        $db->insert_query("settings", $setting);
    }

    rebuild_settings();
}

function userhandles_deactivate()
{
    global $db;

    // Remove the settings
    $db->delete_query("settings", "name ='enable_userhandles'");
    $db->delete_query("settinggroups", "name = 'userhandles'");

    rebuild_settings();
}

// Hook into the profile display
$plugins->add_hook("member_profile_start", "userhandles_display");
function userhandles_display()
{
    global $mybb, $db, $memprofile, $templates, $userhandle;

    if (!$mybb->settings['enable_userhandles']) {
        return;
    }

    $memuid = (int)$memprofile['uid'];
    $uid = (int)$mybb->user['uid'];

    // Fetch all handles for this user
    $query = $db->simple_select("userhandles", "*", "uid='{$memuid}'");
    $handles = [];
    while ($row = $db->fetch_array($query)) {
        // Only show visible handles or allow moderators/owners to view hidden ones
        if (!$row['hidden'] || $mybb->usergroup['canmodcp'] || $memuid == $uid) {
            $hiddenText = $row['hidden'] ? ' (hidden)' : '';
            $handles[] = '<a href="https://v3rm.rip/@' . $row['handle'] . '">@' . $row['handle'] . '</a>' . $hiddenText;
        }
    }

    if (!empty($handles)) {
        $userhandle = '<p>also known as ' . implode(', ', $handles) . '</p>';
    } else {
        // Allow the user to create a handle if they don't have any
        if ($memuid == $uid && ($mybb->user['usergroup'] != 2 /*Members*/ && $mybb->user['usergroup'] != 7 /*Banned*/)) {
            $userhandle = '<h4><a href="/usercp.php?action=handle">Click here to create a custom vanity handle</a></h4>';
        } else {
            $userhandle = '';
        }
    }
}

// Hook into the registration process to add handle
/*
$plugins->add_hook("member_do_register_end", "userhandles_register");
function userhandles_register()
{
    global $mybb, $db, $user_info;

    $handle = $db->escape_string($mybb->input['handle']);
    $timestamp = time();

    $db->insert_query("userhandles", [
        "uid" => $user_info['uid'],
        "handle" => $handle,
        "timestamp" => $timestamp,
        "hidden" => 0,
    ]);
}
*/

$plugins->add_hook("usercp_menu", "userhandles_usercp_menu");
$plugins->add_hook("usercp_start", "userhandles_usercp_page");

function userhandles_usercp_menu()
{
    global $mybb, $db, $templates, $handleop;

    $uid = (int)$mybb->user['uid'];
    $query = $db->simple_select("userhandles", "COUNT(*) as count", "uid='{$uid}'");
    $handleExists = $db->fetch_array($query)['count'] > 0;
    $handleOpText = $handleExists ? "Edit handle" : "Create your handle";
     
    eval('$handleop = "' . $templates->get("usercp_nav_userhandle") . '";');
}

function userhandles_usercp_page()
{
    global $mybb, $db, $templates, $lang, $header, $headerinclude, $footer, $usercpnav;

    // Ensure the user is viewing the correct page
    if ($mybb->input['action'] != "handle") {
        return;
    }

    if ($mybb->user['usergroup'] == 2 /*Members*/)
    {
        error("This feature is for VIP and Elite members only.");
        return;
    }

    // Get current timestamp and calculate timestamp for one week ago (for ratelimit)
    $currentTime = TIME_NOW;
    $oneWeekAgo = $currentTime - (7 * 24 * 60 * 60); // 1 week in seconds

    // Get the user's handle information
    $uid = (int)$mybb->user['uid'];
    $query = $db->simple_select("userhandles", "*", "uid='{$uid}'");
    $userhandle = $db->fetch_array($query);
    if ($userhandle)
    {
        $userhandle['athandle'] = '@' . $userhandle['handle'];
        $currentUserhandleHtml = '<p>Your current handle is <a href="https://v3rm.rip/' . $userhandle['athandle'] . '">' . $userhandle['athandle'] . '</a></p>';
    }


    // Process the form submission if no errors
    if ($mybb->request_method == "post") {
        // Get the last handle change timestamp
        $lastChangeQuery = $db->simple_select("userhandles", "timestamp", "uid='{$uid}' AND timestamp > {$oneWeekAgo}", ["order_by" => "timestamp", "order_dir" => "DESC", "limit" => 1]);
        $lastChange = $db->fetch_field($lastChangeQuery, "timestamp");

        // Check if the user has exceeded the limit
        if ($lastChange && !$mybb->usergroup['canmodcp']) {
            // Calculate the next allowed change time
            $nextAllowedChange = $lastChange + (7 * 24 * 60 * 60); // One week after the last change
            $timeLeft = $nextAllowedChange - $currentTime;
            $timeLeftString = nice_time($timeLeft);

            // Show an error with the time left
            error("You can only change your handle once per week. Please try again in {$timeLeftString}.");
        }

        $newHandle = $db->escape_string($mybb->input['handle']);

        // Strip the '@' character if it's the first character
        if (isset($newHandle[0]) && $newHandle[0] === '@') {
            $newHandle = ltrim($newHandle, '@');
        }

        // Validate the new handle
        if (empty($newHandle)) {
            error("Please provide a valid handle.");
            return;
        } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $newHandle)) {
            error("The handle can only contain letters and numbers.");
            return;
        } elseif (checkBanList($newHandle)) {
            error("This handle includes a sequence of letters that are restricted or prohibited.\n\nIf you think this may be in error, please contact a developer.");
            return;
        }
        elseif ($db->num_rows($db->simple_select("userhandles", "*", "handle='{$newHandle}' AND uid != '{$uid}'")) > 0) {
            error("This handle is already in use.");
            return;
        }

        // Insert or update the user's handle
        if ($userhandle) {
            $db->update_query("userhandles", [
                "handle" => $newHandle,
                "timestamp" => $currentTime
            ], "uid='{$uid}'");
        } else {
            $db->insert_query("userhandles", [
                "uid" => $uid,
                "handle" => $newHandle,
                "timestamp" => $currentTime
            ]);
        }

        redirect("member.php?action=profile&uid={$uid}", "Your custom handle has been successfully updated.");
        
    }

    // Output the form
    $edit_handle = '';
    eval("\$edit_handle = \"" . $templates->get("userhandles_edit_handle") . "\";");
    output_page($edit_handle);
}

function generatePermutations($word) {
    $replacements = [
        '0' => 'o',
        '3' => 'e',
        '1' => 'i',
        '5' => 's'
    ];

    $permutations = [$word];

    foreach ($replacements as $circumvention => $replacement) {
        $newPermutations = [];

        foreach ($permutations as $perm) {
            if (strpos($perm, $circumvention) !== false) {
                $newPermutations[] = str_replace($circumvention, $replacement, $perm);
            }
        }

        $permutations = array_merge($permutations, $newPermutations);
    }

    return array_unique($permutations);
}

function checkBanList($word) {
    $permutations = generatePermutations(strtolower($word));
    $banList = ['REDACTED'];
    $endsWithBanList = ['REDACTED'];

    foreach ($permutations as $perm) {
        foreach ($banList as $wordInList) {
            if (strpos($perm, $wordInList) !== false) {
                return true;
            }
        }
        foreach ($endsWithBanList as $wordInList) {
            if (endsWith($perm, $wordInList) !== false) {
                return true;
            }
        }
    }

    return false;
}

function endsWith($haystack, $needle) // for PHP 7
{
    return substr_compare($haystack, $needle, -strlen($needle)) === 0;
}
?>