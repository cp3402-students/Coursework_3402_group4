<?php
// Simple Progress bar
function calculateProgress($total, $complete)
{
    if ($total === 0) {
        $percentageComplete = 0;
    } else {
        $roundedComplete = (int)round(($complete / $total) * 100);
        if (100 === $roundedComplete && 1 !== ($complete / $total)) {
            $percentageComplete = 99;
        } else {
            $percentageComplete = $roundedComplete;
        }
        // $percentageComplete = (int)round(($complete/$total)*100);
    }
    return $percentageComplete;
}

/**
 * Determine if the maximum Execution Time
 * has been reached since the start time
 * of the latest process
 * 
 * @return boolean False if max time not reached. True if max time is reached
 */
function calculateProcessElapsedTime($process)
{
    $maxExecTimeAllowed = ini_get('max_execution_time');

    if (false === $maxExecTimeAllowed || 0 === $maxExecTimeAllowed) {
        return false;
    }

    if (false === get_option('wpm2aws_' . $process . '_process_start_time')) {
        return false;
    }

    /*
    * Establish Start Time
    * Establish Max End Time
    * Check Against Current Time
    */
    $start = get_option('wpm2aws_' . $process . '_process_start_time');
    $allowedEnd = $start + $maxExecTimeAllowed;
    if (time() > $allowedEnd) {
        return true;
    }
    return false;
}

$progressCompete = 'error';
$lastStartTime = 'error';


// Get & Calculate the Current Progress Status
if (false !== get_option('wpm2aws_zipped_fs_upload_started')) {
    $process = 'zipped_fs_upload';
} else if (false !== get_option('wpm2aws_fszipper_started')) {
    $process = 'fszipper';
} else if (false !== get_option('wpm2aws_upload_started')) {
    $process = 'upload';
} else {
    $process = 'zipped_fs_upload';
}

$counterOptionName = 'wpm2aws_' . $process . '_counter';
if (false !== get_option($counterOptionName) && is_array(get_option($counterOptionName))) {
    $total = 0;
    $complete = 0;
    $progress = get_option($counterOptionName);
    $total = (isset($progress['total']) ? $progress['total'] : 0);
    $complete = (isset($progress['complete']) ? $progress['complete'] : 0);
    $progressCompete = calculateProgress($total, $complete);
}


// if (false !== get_option('wpm2aws_upload_started')) {
//     if (false !== get_option('wpm2aws_upload_counter') && is_array(get_option('wpm2aws_upload_counter'))) {
//         // $process = 'upload';
//         $total = 0;
//         $complete = 0;
//         $progress = get_option('wpm2aws_upload_counter');
//         $total = (isset($progress['total']) ? $progress['total'] : 0);
//         $complete = (isset($progress['complete']) ? $progress['complete'] : 0);
//         $progressCompete = calculateProgress($total, $complete);
//     }
// }

// if (false !== get_option('wpm2aws_fszipper_started')) {
//     if (false !== get_option('wpm2aws_download_counter') && is_array(get_option('wpm2aws_download_counter'))) {
//         // $process = 'download';
//         $total = 0;
//         $complete = 0;
//         $progress = get_option('wpm2aws_download_counter');
//         $total = (isset($progress['total']) ? $progress['total'] : 0);
//         $complete = (isset($progress['complete']) ? $progress['complete'] : 0);
//         $progressCompete = calculateProgress($total, $complete);
//     }
// }

// if (false !== get_option('wpm2aws_admin_upload_started')) {
//     if (false !== get_option('wpm2aws_admin_upload_counter') && is_array(get_option('wpm2aws_admin_upload_counter'))) {
//         // $process = 'admin_upload';
//         $total = 0;
//         $complete = 0;
//         $progress = get_option('wpm2aws_admin_upload_counter');
//         $total = (isset($progress['total']) ? $progress['total'] : 0);
//         $complete = (isset($progress['complete']) ? $progress['complete'] : 0);
//         $progressCompete = calculateProgress($total, $complete);
//     }
// }

// Get the start time of the most recent process
// Assess if more than the allowed processing time has elapsed (Server Determined)
// return 
$maxTimeExceeded = calculateProcessElapsedTime($process);


$response = array(
    'process' => $process,
    'progressComplete' => $progressCompete,
    'maxTimeExceeded' => $maxTimeExceeded,
);


$response = json_encode($response);
header("Content-type: application/json", true);
echo $response;
exit;
