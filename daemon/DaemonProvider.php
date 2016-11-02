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
     * @return bool
     */
    protected function init()
    {
        return true;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return \logger\Logger::class;
    }

    /** @inheritdoc */
    public function start()
    {
        if ($this->isWork()) {
            return false;
        }
        file_put_contents(static::PID_FILE, posix_getpid());
        self::log('Starting daemon, pid: ' . posix_getpid());
        $this->init();

        while (!$this->stop_server) {
            $this->loop();
        }
        $this->removePidFile();
        self::log('Daemon is stopped');

        return true;
    }

    /**
     * Daemon constructor.
     */
    public function __construct()
    {
        if (!static::PID_FILE || !static::LOG_FILE) {
            throw new \Exception('Please declare PID_FILE and LOG_FILE constants');
        }
        pcntl_signal(SIGTERM, array($this, 'sigHandler'));
        pcntl_signal_dispatch();
        register_shutdown_function(array($this, 'shutdownHandler'));
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
                self::log('Found daemon pid file #' . $pid);
                $this->removePidFile();

                return false;
            } else {
                self::log('Daemon is work, pid #' . $pid);
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
        if (is_file(static::PID_FILE)) {
            $string = file_get_contents(static::PID_FILE);
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
                self::log('Stopping daemon, pid: ' . posix_getpid());
                break;
            default:
        }
    }

    /**
     * Catching stop daemon event
     */
    public function shutdownHandler()
    {
        if (!posix_kill(self::getPid(), 0)) {
            $this->removePidFile();
        }
    }

    /**
     * @return bool
     */
    public function removePidFile()
    {
        if (is_file(static::PID_FILE)) {
            unlink(static::PID_FILE);
            self::log('Remove pid file');

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
        $logger = self::getLogger();
        /** @var Logger $logger */
        $logger = new $logger;
        $logger->log($level, $message);
        if (method_exists($logger, 'createString')) {
            file_put_contents(self::LOG_FILE, $logger::createString($level, $message), FILE_APPEND);
        } else {
            file_put_contents(self::LOG_FILE, date('Y/m/d H:i:s') . ' -> ' . $message . "\n", FILE_APPEND);
        }
    }

    /**
     * Function worked in loop while daemon working
     *
     * @return mixed
     */
    abstract public function loop();
}
