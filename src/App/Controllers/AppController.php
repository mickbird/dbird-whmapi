<?php
declare(strict_types = 1);

namespace App\Controllers;

use App\Components\DashboardComponent;
use App\MyApp;
use App\Components\CsrfComponent;
use App\Components\FlashComponent;
use App\Components\HtmlComponent;
use App\Components\I18nComponent;
use App\Components\SecurityComponent;
use Core\Controller;
use Core\ServerRequest;
use Core\ServerResponse;

/**
 * Class AppController provides customized and common functionnality
 * @package App\Controllers
 */
abstract class AppController extends Controller
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
     * AppController constructor.
     * @param \Core\ServerRequest $request
     * @param \Core\ServerResponse $response
     */
    public function __construct(ServerRequest $request, ServerResponse $response)
    {
        parent::__construct($request, $response);
    }

    /*
     * PUBLIC METHODS
     */

    /**
     * Invoked before the controller call redirect method.
     * Disable redirection if the request is made by ajax (API aware)
     * @param  mixed $url
     * @return bool True to continue processing. False to stop processing
     */
    protected function beforeRedirect($url) : bool
    {
        if (parent::beforeRedirect($url) === false) {
            return false;
        } elseif ($this->getRequest()->isAjax()) {
            return false;
        } else {
            return true;
        }
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /*
     * STATIC METHODS
     */
}
