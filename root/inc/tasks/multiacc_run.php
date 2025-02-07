<?php

function task_multiacc_run($task) {
    global $db;

    $query = $db->simple_select('ip_history', 'ip, GROUP_CONCAT(uid) as uids', '1=1', ['group_by' => 'ip', 'having' => 'COUNT(*) > 1']);   
    while ($row = $db->fetch_array($query)) {
        $multi = explode(',', $row['uids']);
        
        if (count($multi) > 1) {
            foreach ($multi as $uid) {
                foreach ($multi as $alt_uid) {

                    if ($uid == $alt_uid) continue;

                    $exists_query = $db->simple_select('multiaccounts', 'id, timestamp', "uid={$uid} AND alt_uid={$alt_uid}");
                    if ($db->num_rows($exists_query) == 0) {

                        $insert_array = [
                            'uid' => (int)$uid,
                            'alt_uid' => (int)$alt_uid,
                            'timestamp' => (int)$row['createdate']
                        ];
                        $db->insert_query('multiaccounts', $insert_array);
                    }
                }
            }
        }
    }
	
	add_task_log($task, "The multi-account task successfully ran.");
}

?>