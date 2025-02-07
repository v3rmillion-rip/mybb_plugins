<?php
/**
* Git Sync Task for MyBB
* by JP (yakov)
*/

function task_syncgit($task)
{
    $repoDir = '/var/www/v3rmillion.rip';
    
    // Check for changes
    $statusOutput = shell_exec("git -C " . escapeshellarg($repoDir) . " status --porcelain -uno");
    
    if (trim($statusOutput) !== '') {
        // Stage only changes to tracked files (modifications and deletions)
        shell_exec("git -C " . escapeshellarg($repoDir) . " add -u");
        
        // Commit changes
        $commitMessage = 'Auto-sync from MyBB task';
        shell_exec("git -C " . escapeshellarg($repoDir) . " commit -m " . escapeshellarg($commitMessage));
        
        // Push changes
        $pushOutput = shell_exec("git -C " . escapeshellarg($repoDir) . " push origin main 2>&1");
        
        // Log task execution
        add_task_log($task, 'Git sync completed. Push output: ' . $pushOutput);
    } else {
        // Log if no changes were found
        add_task_log($task, 'No changes to commit and push.');
    }
}
?>