<?php
class WPM2AWS_RunProcess extends WP_Background_Process
{
    use WPM2AWS_Logger;
    /**
     * @var string
     */
    protected $action = 'wpm2aws-uploader-all';
    /**
     * Task
     *
     * Override this method to perform any actions required on each
     * queue item. Return the modified item for further processing
     * in the next pass through. Or, return false to remove the
     * item from the queue.
     *
     * @param mixed $item Queue item to iterate over
     *
     * @return mixed
     */
    protected function task($item)
    {
        // wp_die('task');
        // Sleep for 2seconds after every 5% of file uploads complete
        $this->pauseProcess();
        
        // Run the "Upload File Or Directory" Process
        $uploaded = $this->backgroundUploadToS3($item);
    
        // Dev / Testing Logging
        if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
            $this->log("Upload Result (final): " . $uploaded);
        }

        /*
        * Only Recognised responses result in successful upload
        * Only allow Failed ITems to re-run 5x times
        * then log this fatal error and
        * remove item from queue
        */
        if ((string)$uploaded !== '200' && (string)$uploaded !== '404') {
            // Check if process has run max amount of times
            $permenantFail = $this->ispermenantFail($item);

            /* If processing of the item
            * has been attempted
            * to be processed more than
            * the max permissible attempts
            * the send this item into
            * the next process
            * (by assigining it a recognised response)
            * which results in it being removed from queue
            *
            * Otherwise attempt to re-process the item
            */
            if (true === $permenantFail) {
                $uploaded = '404';
            } else {
                $this->log("Upload Failed - Returning to Background Queue");
                return $item;
            }
        }

        /* If sucessful, lof the result (Dev / Testing only)
        * Otherwise
        * Update Register of Failed Uploads and
        * Log the Failure
        */
        if ((string)$uploaded === '200') {
            if (defined('WPM2AWS_TESTING') || defined('WPM2AWS_DEBUG') || defined('WPM2AWS_DEV')) {
                $this->log("Upload Success");
            }
        } else if ((string)$uploaded === '404') {
            $failedUserNotice = '';
            if (false !== get_option('wpm2aws_upload_failures')) {
                $failedUserNotice = get_option('wpm2aws_upload_failures');
            }

            // Remove any assigned suffixes for zips
            $trimmedItemName = str_replace('.wpm2awsZipDir', '', $item);
            $failedUserNotice .= $trimmedItemName . "<br>";

            // Add to register
            wpm2awsAddUpdateOptions('wpm2aws_upload_failures', $failedUserNotice);      

            // Log Failure
            $this->log("Upload Failed (404)");
        } else {
            $this->log("Upload Failed (unknown) - " . $uploaded);
        }
        
        // Update the progress tracker
        $this->updateProgressCounter();

        // Remove item from Queue
        return false;
    }


    /**
     * Complete
     *
     * Override if applicable, but ensure that the below actions are
     * performed, or, call parent::complete().
     */
    protected function complete()
    {
        parent::complete();

        // Remove the temp directory for zipped files
        $pluginsPath = str_replace('wp-migrate-2-aws', '', WPM2AWS_PLUGIN_DIR);
        $zipTempDirectory = $pluginsPath . WPM2AWS_ZIP_EXPORT_PATH;

        deleteDirectoryTree($zipTempDirectory);

        // Show notice to user or perform some other arbitrary task...
        $this->log('Upload Process Complete - ' . date("d-m-Y @ H:i:s"));
        
        wpm2awsAddUpdateOptions('wpm2aws_upload_complete', 'error');
        if (false === get_option('wpm2aws_upload_errors') || empty(get_option('wpm2aws_upload_errors'))) {
            wpm2awsAddUpdateOptions('wpm2aws_upload_complete', 'success');
        }

        delete_option('wpm2aws_upload_process_start_time');
    }
    

    /**
     * Unused function
     *
     * @return void
     */
    public function finaliseHandler()
    {
        // $this->log('*********** All Handled *************');
    }


    /**
     * Checks if an item has been attemted to be uploaded
     * a set amount of attempts - dafault 5
     * If greater than allowed number of attempts, then consider a
     * permenant fail (return true)
     *
     * @param string $uploadItem The path to item being uploaded
     * @param int $maxAttempts Number of allowed attempts - default = 5
     * @return boolean
     */
    protected function isPermenantFail(string $uploadItem, $maxAttempts = 5)
    {
        // If there are no attempts yet made,
        // Set counter to "1" and return item to queue
        // Otherwise, get the current number of attempts
        if (false === get_option('wpm2aws_bgProcessAttempts')) {
            $attempts = 1;
            $attemptsUpdate = array(
                $uploadItem => $attempts
            );
            wpm2awsAddUpdateOptions('wpm2aws_bgProcessAttempts', $attemptsUpdate);
            // Return to queue
            $this->log("Returning item ( " . $uploadItem . " ) to Queue - attempt: " . $attempts);
            return false;
        } else {
            $failedUploads = get_option('wpm2aws_bgProcessAttempts');

            // If this item has already been attempted
            // and if number of attempts is less than 5
            // re-attempt the upload
            // Otherwise, set the "attempts" counter as "1"
            if (isset($failedUploads[$uploadItem])) {
                $attempts = $failedUploads[$uploadItem];
                if ($attempts < $maxAttempts) {
                    $attempts++;
                } else {
                    $attempts = 0;
                }
            } else {
                $attempts = 1;
            }

            // Update the "attempts" option
            $failedUploads[$uploadItem] = $attempts;
            if ($attempts < 1) {
                unset($failedUploads[$uploadItem]);
            }
            wpm2awsAddUpdateOptions('wpm2aws_bgProcessAttempts',  $failedUploads);
            

            // If "attempts" is greater than "0" AND less-than or equal to "max attempts"
            // then return item to the list
            // otherwise, remove from list & register in Logs 
            if ($attempts > 0 && $attempts <= $maxAttempts) {
                // Return to queue
                $this->log("Returning item to Queue - attempt: " . $attempts);
                return false;
            } else {
                // remove item from queue
                $this->log("Item is considered a permenant fail - " . $uploadItem);
                // is condsidered a permenant fail
                return true;
            }
        }
    }


    /**
     * Update the file-upload progress counter
     * Add if not exits
     * Otherwise update "complete" value
     *
     * @return void
     */
    protected function updateProgressCounter()
    {
        $progress = get_option('wpm2aws_upload_counter');
        $total = $progress['total'];    
        $complete = $progress['complete'];
        if (empty($complete)) {
            $complete = 1;
        } else {
            $complete++;
        }

        $update = array(
            'total' => $total,
            'complete' => $complete
        );
        update_option('wpm2aws_upload_counter', $update);
        return;
    }


    /**
     * Pause background process every nth itteration
     * Default to pause for 2 seconds after every 5% comlete
     * Allow DB to be freed up for other requests
     *
     * @param integer $pauseDuration Lenght of the pause in mili-seconds (default 2 seconds)
     * @param integer $pauseFrequency How often Pause should occrur (as a percentage of overall)
     * @return void
     */
    protected function pauseProcess($pauseDuration = 2000000, $pauseFrequency = 5)
    {
        // Get progress status
        $progress = get_option('wpm2aws_upload_counter');
        $total = (isset($progress['total']) ? $progress['total'] : 0);
        $complete = (isset($progress['complete']) ? $progress['complete'] : 0);
        if ($total === 0) {
            $percentageComplete = 0;
        } else {
            $percentageComplete = (int)round(($complete/$total)*100);
        }

        // If this itteration is at pause-point
        if ($percentageComplete > 0 && $percentageComplete < 100) {
            if ($percentageComplete % $pauseFrequency === 0) {
                // wait for 2 (default) seconds
                $this->log("Pausing Process at: " . $percentageComplete . "% complete");
                usleep($pauseDuration);
            }
        }
        return true;
    }
}
