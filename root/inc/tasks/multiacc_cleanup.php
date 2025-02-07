<?php

function determine_ip_certainty($ip, $alt_ip)
{
    if ($ip == $alt_ip) {
        return 1;
    }

    return 0;
}

function task_multiacc_cleanup($task) {
    global $db;

    $query = $db->simple_select("multiaccounts", "*");
    while ($row = $db->fetch_array($query)) {
        $id = (int)$row['id'];
        $uid = (int)$row['uid'];
        $alt_uid = (int)$row['alt_uid'];
        $certainty = (int)$row['certainty'];
        $timestamp = (int)$row['timestamp'];

        // Check if the IPs still match
        $ip_history_uid = $db->simple_select('ip_history', 'ip, createdate', "uid = '{$uid}'", ['order_by' => 'createdate', 'order_dir' => 'DESC', 'limit' => 1]);
        $ip_history_alt_uid = $db->simple_select('ip_history', 'ip, createdate', "uid = '{$alt_uid}'", ['order_by' => 'createdate', 'order_dir' => 'DESC', 'limit' => 1]);

        $uid_ip = $db->fetch_field($ip_history_uid, 'ip');
        $alt_uid_ip = $db->fetch_field($ip_history_alt_uid, 'ip');
        $ip_history_timestamp = max($db->fetch_field($ip_history_uid, 'createdate'), $db->fetch_field($ip_history_alt_uid, 'createdate'));

        if (empty($uid_ip) || empty($alt_uid_ip) || $uid_ip == 1 || $alt_uid_ip == 1) continue;

        $newCertainty = determine_ip_certainty($uid_ip, $alt_uid_ip);
        if ($certainty !== $newCertainty) {
            $db->update_query('multiaccounts', ['certainty' => $newCertainty], "id = '{$id}'");
        }

        if ($ip_history_timestamp > $timestamp) {
            $db->update_query('multiaccounts', ['timestamp' => $ip_history_timestamp], "id = '{$id}'");
        }
    }
    
    add_task_log($task, "The multi-account check task successfully ran.");
}