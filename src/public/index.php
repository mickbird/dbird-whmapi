<?php
declare(strict_types = 1);

use App\MyApp;

ini_set('xdebug.var_display_max_depth', '10');
define('__BASE__', dirname(__DIR__));

/**
 * Composer
 */
require_once __BASE__ . '/vendor/autoload.php';
require_once __BASE__ . '/App/Libs/Utilities.php';

/**
 * Error and Exception handling
 */
error_reporting(E_ALL);
set_error_handler('Core\ErrorHandler::handleError');
set_exception_handler('Core\ErrorHandler::handleThrowable');

/**
 * Routing
 */
MyApp::current()->start();
