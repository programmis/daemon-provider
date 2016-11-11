<?php
/**
 * Created by PhpStorm.
 * User: daniil
 * Date: 25.05.16
 * Time: 16:26
 */

namespace daemon;

declare(ticks = 1);

use logger\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Class Daemon
 *
 * @package common\daemons
 */
abstract class DaemonProvider implements DaemonInterface
{
    /**
     * @var bool
     */
    private $stop_server = false;
    /**
     * @var bool
     */
    private $i_am_child = false;

    /**
     * @return bool
     */
    protected function init()
    {
        return true;
    }

    /**
     * @return LoggerInterface
     */
    public static function getLogger()
    {
        return new Logger();
    }

    /** @inheritdoc */
    public static function getPidFile()
    {
        return static::PID_FILE;
    }

    /** @inheritdoc */
    public static function getLogFile()
    {
        return static::LOG_FILE;
    }

    /** @inheritdoc */
    public function start()
    {
        if ($this->isWork()) {
            return false;
        }
        file_put_contents(static::getPidFile(), posix_getpid());
        static::log('Starting daemon, pid: ' . posix_getpid());
        $this->init();

        while (!$this->stop_server) {
            $this->loop();
        }
        $this->removePidFile();
        static::log('Daemon is stopped');

        return true;
    }

    /**
     * @throws \Exception
     */
    protected function checkConstants()
    {
        if (!static::getPidFile() || !static::getLogFile()) {
            throw new \Exception('Please declare PID_FILE and LOG_FILE constants');
        }
    }

    /**
     * for stop method
     */
    public function initEnvironment()
    {
        pcntl_signal(SIGTERM, array($this, 'sigHandler'));
        pcntl_signal_dispatch();
        register_shutdown_function(array($this, 'shutdownHandler'));
    }

    /**
     * Daemon constructor.
     */
    public function __construct()
    {
        $this->checkConstants();
        $this->initEnvironment();
    }

    /** @inheritdoc */
    public static function stop()
    {
        $pid = self::getPid();
        if ($pid !== 0) {
            posix_kill($pid, SIGTERM);
        }
    }

    /**
     * @return bool
     */
    private function isWork()
    {
        $pid = self::getPid();
        if ($pid) {
            if (!posix_kill(self::getPid(), 0)) {
                static::log('Found daemon pid file #' . $pid);
                $this->removePidFile();

                return false;
            } else {
                static::log('Daemon is work, pid #' . $pid);
            }

            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    private static function getPid()
    {
        $pid = 0;
        if (is_file(static::getPidFile())) {
            $string = file_get_contents(static::getPidFile());
            if (is_numeric($string)) {
                $pid = (int)$string;
            }
        }

        return $pid;
    }

    /**
     * @param $signo
     */
    public function sigHandler($signo)
    {
        switch ($signo) {
            case SIGTERM:
                $this->stop_server = true;
                static::log('Stopping daemon, pid: ' . posix_getpid());
                break;
            default:
        }
    }

    /**
     * mark current process as children
     */
    public function childEnable()
    {
        $this->i_am_child = true;
    }

    /**
     * Catching stop daemon event
     */
    public function shutdownHandler()
    {
        if (!posix_kill(self::getPid(), 0)) {
            if ($this->i_am_child) {
                return;
            }
            $this->removePidFile();
        }
    }

    /**
     * @return bool
     */
    public function removePidFile()
    {
        if (is_file(static::getPidFile())) {
            unlink(static::getPidFile());
            static::log('Remove pid file');

            return true;
        }

        return false;
    }

    /**
     * @param      $message
     * @param      $level
     */
    public static function log($message, $level = LogLevel::INFO)
    {
        $logger = static::getLogger();

        $logger->log($level, $message);
        if (method_exists($logger, 'createString')) {
            file_put_contents(static::getLogFile(), $logger::createString($level, $message), FILE_APPEND);
        } else {
            file_put_contents(static::getLogFile(), date('Y/m/d H:i:s') . ' -> ' . $message . "\n", FILE_APPEND);
        }
    }

    /**
     * Function worked in loop while daemon working
     *
     * @return mixed
     */
    abstract public function loop();
}
