<?php
/**
 * User: adityapatadia
 * Date: 27.04.2020
 */

class GumletLogger {
    private static $instance;
    private $logPath;
    /**
     * Make sure only one instance is running.
     */
    public static function instance()
    {
        if (!isset (self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        $upload_dir = wp_upload_dir();
        $this->logPath = $upload_dir['basedir'] . '/gumlet_logs';
        if (GUMLET_DEBUG === 'delete') {
            @unlink($this->logPath);
            $this->log("START FRESH", GUMLET_DEBUG);
        }
    }

    public function log($msg, $extra = false) {
        if (GUMLET_DEBUG) {
            file_put_contents($this->logPath, '[' . date('Y-m-d H:i:s') . "] $msg" . ($extra ? json_encode($extra, JSON_PRETTY_PRINT) : '') . "\n", FILE_APPEND);
        }
    }
}
