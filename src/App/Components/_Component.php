<?php
declare(strict_types = 1);

namespace App\Components;

use Core\Component;
use Core\Controller;

/**
 *
 * @package App\Components
 */
class _Component extends Component
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
     * _Component constructor.
     */
    public function __construct()
    {
    }

    /*
     * PUBLIC METHODS
     */

    /**
     * Invoked before the controller call filter method.
     * @param Controller $controller The calling controller
     * @return bool True to continue processing. False to stop processing
     */
    public function beforeFilter(Controller $controller) : bool
    {
        return true;
    }

    /**
     * Invoked before the controller call render method.
     * @param Controller $controller The calling controller
     */
    public function beforeRender(Controller $controller) : void
    {
    }

    /**
     * Invoked before the controller call redirect method.
     * @param Controller $controller The calling controller
     * @return bool True to continue processing. False to stop processing
     */
    public function beforeRedirect(Controller $controller) : bool
    {
        return true;
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /*
     * STATIC METHODS
     */
}
