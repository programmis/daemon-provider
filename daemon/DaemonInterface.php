<?php
/**
 * Created by PhpStorm.
 * User: daniil
 * Date: 25.05.16
 * Time: 16:31
 */

namespace daemon;

/**
 * Interface DaemonInterface
 *
 * @package common\daemons
 */
interface DaemonInterface
{
    /** Path to pid file */
    const PID_FILE = null;
    /** Path to log file */
    const LOG_FILE = null;

    /**
     * Returned last crashing daemon message
     *
     * @return string
     */
    public static function getLastCrashMessage();

    /**
     * @return bool
     */
    public function start();

    /**
     * stopping the daemon
     */
    public static function stop();

    /**
     * @return string
     */
    public static function getPidFile();

    /**
     * @return string
     */
    public static function getLogFile();
}
