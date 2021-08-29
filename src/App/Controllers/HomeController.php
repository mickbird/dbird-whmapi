<?php
declare(strict_types = 1);

namespace App\Controllers;

class HomeController extends AppController
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
    
    public function indexAction() : void
    {
        $this->set('php_version', phpversion());
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */
    
    /*
     * STATIC METHODS
     */
}
