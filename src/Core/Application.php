<?php
//declare(strict_types = 1);

namespace Core;

use App\Config;
use App\Libs\LoggerFactory;

abstract class Application
{
    /*
     * FIELDS
     */

    protected Config $config;
    protected LoggerFactory $loggerFactory;
    protected Router $router;
    protected ViewBuilder $viewBuilder;

    /*
     * GETTERS / SETTERS
     */

    /**
     * Get the configuration store
     * @return \App\Config
     */
    public function getConfig() : Config
    {
        return $this->config;
    }

    /**
     * Get the loggerFactory
     * @return \App\Libs\LoggerFactory
     */
    public function getLoggerFactory() : LoggerFactory
    {
        return $this->loggerFactory;
    }

    /**
     * Get the router
     * @return \Core\Router
     */
    public function getRouter() : Router
    {
        return $this->router;
    }

    /**
     * Get the viewBuilder
     * @return \Core\ViewBuilder
     */
    public function getViewBuilder() : ViewBuilder
    {
        return $this->viewBuilder;
    }

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /**
     * Application constructor.
     */
    public function __construct()
    {
        /**
         * Configuration initialization
         */
        $this->config = new Config(join(DIRECTORY_SEPARATOR, [__BASE__]));

        /**
         * Logging
         */
        $this->loggerFactory = new LoggerFactory(join(DIRECTORY_SEPARATOR, [__BASE__, 'Logs', 'app.log']), $this->getConfig()->getLogLevel());

        /**
         * View initialization
         */
        $this->viewBuilder = new ViewBuilder(join(DIRECTORY_SEPARATOR, [__BASE__, 'App', 'Views']));

        if ($this->getConfig()->getEnvironment() === 'development') {
            $this->viewBuilder->enableDebug();
        }

        /**
         * Routing
         */
        $this->router = new Router();
    }

    /*
     * PUBLIC METHODS
     */

    /**
     * Initialize the application and start processing query string
     * @throws \Exception
     */
    public function start()
    {
        $this->initialize();
        $this->router->dispatch($_SERVER['REQUEST_URI']);
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /**
     * Initialize the application. Acts like a lightweight constructor
     */
    protected function initialize()
    {
    }

    /*
     * STATIC METHODS
     */

    /**
     * Get the current running application instance
     * @return \Core\Application|null
     */
    public static function current() : ?Application
    {
        return @$GLOBALS['App'];
    }
}
