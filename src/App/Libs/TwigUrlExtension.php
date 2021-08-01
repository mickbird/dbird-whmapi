<?php
declare(strict_types = 1);

namespace App\Libs;

use App\Helpers\UrlHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigUrlExtension extends AbstractExtension
{
    /*
     * FIELDS
     */

    protected UrlHelper $url;

    /*
     * GETTERS / SETTERS
     */

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /**
     * TwigUrlExtension constructor.
     */
    public function __construct()
    {
        $this->url = new UrlHelper();
    }

    /*
     * PUBLIC METHODS
     */

    /**
     * Extension name
     * @return string
     */
    public function getName() : string
    {
        return 'url_buid_twig_extension';
    }

    /**
     * List of functions provided by the extension
     */
    public function getFunctions() : array
    {
        return [
            new TwigFunction('url', [$this->url, 'build']),
        ];
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /*
     * STATIC METHODS
     */
}
