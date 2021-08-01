<?php
declare(strict_types = 1);

namespace Core;

use ErrorException;
use Monolog\Logger;

/**
 * Error and exception handler
 */
class ErrorHandler
{
    /*
     * FIELDS
     */

    /*
     * GETTERS / SETTERS
     */

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /*
     * PUBLIC METHODS
     */

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /*
     * STATIC METHODS
     */

    /**
     * Error handler. Convert all errors to Exceptions by throwing an ErrorException.
     *
     * @param int $level  Error level
     * @param string $message  Error message
     * @param string $file  Filename the error was raised in
     * @param int $line  Line number in the file
     *
     * @return void
     */
    public static function handleError(int $level, string $message, string $file, int $line)
    {
        if (error_reporting() !== 0) {  // to keep the @ operator working
            throw new ErrorException($message, 500, $level, $file, $line);
        }
    }

    /**
     * Throwable handler.
     *
     * @param \Throwable $throwable The throwable
     *
     * @return void
     */
    public static function handleThrowable(\Throwable  $throwable)
    {
        $throwableClass = get_class($throwable);
        $statusCode = $throwable->getCode();

        // Code is 404 (not found) or 500 (general error)
        if ($statusCode !== 404) {
            $statusCode = 500;
        }

        http_response_code($statusCode);

        if (Application::current() === null || Application::current()->getConfig()->getEnvironment() !== 'production') {
            $message = <<< HTML
                <h1>Fatal error - {$throwable->getCode()}</h1>
                <p>Uncaught exception: '{$throwableClass}'</p>
                <p>Message: '{$throwable->getMessage()}'</p>
                <p>Stack trace:<pre>{$throwable->getTraceAsString()}</pre></p>
                <p>Thrown in '{$throwable->getFile()}' on line {$throwable->getLine()}</p>
            HTML;
            echo $message;
            return;
        } elseif (($logger = Application::current()->getLoggerFactory()->getInstance(__CLASS__)) !== null) {
            $level = static::determineLogLevel($throwable->getCode());
            $logger->log($level, "Uncaught exception: '{$throwableClass} with message '{$throwable->getMessage()}'", ['exception' => $throwable]);
        } else {
            $log = __BASE__ . '/Logs/' . date('Y-m-d') . '.log';
            ini_set('error_log', $log);

            $message = <<< HTML
                Uncaught exception: '{$throwableClass} with message '{$throwable->getMessage()}'
                Stack trace: {$throwable->getTraceAsString()}
                Thrown in '{$throwable->getFile()}' on line {$throwable->getLine()};
                HTML;

            error_log($message);
        }

        echo Application::current()->getViewBuilder()->build("Error/{$statusCode}.html.twig", [
            'title' => "Error {$statusCode}",
            'exception' => $throwable
        ]);
    }

    protected static function determineLogLevel(int $errorCode) : int
    {
        if ((int)($errorCode / 100) === 4) {
            return Logger::INFO;
        } elseif ((int)($errorCode / 100) === 5) {
            return Logger::ERROR;
        } elseif ($errorCode === 0) {
            return Logger::CRITICAL;
        } else {
            return Logger::EMERGENCY;
        }
    }
}
