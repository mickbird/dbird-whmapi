<?php
declare(strict_types = 1);

namespace App\Libs;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class LoggerFactory
{
    /*
     * FIELDS
     */

    private array $loggers;
    protected string $logFile;
    protected int $logLevel;

    /*
     * GETTERS / SETTERS
     */

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /**
     * LoggerFactory constructor.
     * @param string $logFile
     * @param int $logLevel
     */
    public function __construct(string $logFile, int $logLevel)
    {
        $this->logFile = $logFile;
        $this->logLevel = $logLevel;
    }

    /*
     * PUBLIC METHODS
     */

    /**
     * Create a new Logger with the specific settings
     */
    public function create(string $key, ?array $config = null) : Logger
    {
        $logger = new Logger($key);

        $logger->pushHandler(new RotatingFileHandler(
            @$config['logFile'] ?? $this->logFile,
            @$config['maxFiles'] ?? 0,
            @$config['logLevel'] ?? $this->logLevel
        ));

        return $logger;
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /*
     * STATIC METHODS
     */

    /**
     * Singleton / factory method for getting/creating loggers
     * @param string|null $key
     * @param array|null $config
     * @return \Monolog\Logger
     */
    public function getInstance(?string $key = 'app', ?array $config = null) : Logger
    {
        $this->loggers[$key] ??= $this->create($key, $config);

        return $this->loggers[$key];
    }
}
