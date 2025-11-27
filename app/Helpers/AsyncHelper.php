<?php

class AsyncHelper {
    /**
     * Executes the email worker script in the background.
     *
     * @param string $emailType The type of email to send (e.g., 'welcome_email', 'payment_submission').
     * @param int $id The ID of the relevant entity (e.g., UserID, BookingID).
     */
    public static function triggerEmailWorker($emailType, $id) {
        // This path might need to be configured if PHP is installed elsewhere.
        $phpPath = 'C:\\xampp\\php\\php.exe';
        
        $workerScriptPath = realpath(__DIR__ . '/../../scripts/send_email_worker.php');

        if (!$workerScriptPath) {
            error_log("FATAL: Email worker script not found at {$workerScriptPath}.");
            return;
        }

        // Command for Windows to run a process in the background without a visible window.
        $command = 'start /B "" "' . $phpPath . '" "' . $workerScriptPath . '" ' . escapeshellarg($emailType) . ' ' . escapeshellarg($id);
        
        // For Linux/macOS, the equivalent command would be:
        // $command = escapeshellcmd($phpPath) . ' ' . escapeshellarg($workerScriptPath) . ' ' . escapeshellarg($emailType) . ' ' . escapeshellarg($id) . ' > /dev/null 2>&1 &';

        pclose(popen($command, 'r'));
    }
}