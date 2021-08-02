<?php
declare(strict_types = 1);

namespace App;

use App\Libs\SessionIdentifierHandler;
use Core\Application;
use Core\Router;

class MyApp extends Application
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

    /**
     * Initialize the application. Acts like a lightweight constructor
     */
    protected function initialize() : void
    {
        // Stub method for i18n handling by POEdit. Delay translation for UrlHelper.
        function _($str) { return $str; }

        /**
         * Routing
         */
        $this->router = new Router();
        $this->router
            ->connect('default_full_route', '{controller}/{action}')
            ->connect('default_default_action', '{controller}', ['action' => 'index'])
            ->connect('default_default_ctrl_action', '', ['controller' => 'Home', 'action' => 'index', 'extension' => '.html']);
    }

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
     * Get the current running application instance
     * @return \App\MyApp
     */
    public static function current() : MyApp
    {
        $GLOBALS['App'] ??= new MyApp();

        return parent::current();
    }
}
