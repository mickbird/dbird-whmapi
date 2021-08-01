<?php
declare(strict_types = 1);

namespace Core;

use App\Libs\TwigUrlExtension;
use jblond\TwigTrans\Translation;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

/**
 * View
 */
class ViewBuilder
{
    /*
     * FIELDS
     */

    protected Environment $twig;

    /*
     * GETTERS / SETTERS
     */

    /**
     * Enable debugging in Twig
     */
    public function enableDebug()
    {
        $this->twig->enableDebug();
    }

    /**
     * Disable debugging in Twig
     */
    public function disableDebug()
    {
        $this->twig->disableDebug();
    }

    /**
     * Indicate if debug is enabled in Twig
     */
    public function isDebug()
    {
        $this->twig->isDebug();
    }

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /**
     * ViewBuilder constructor.
     * @param string $path
     */
    public function __construct(string $path)
    {
        $loader = new FilesystemLoader($path);
        $this->twig = new Environment($loader);

        $this->twig->addExtension(new StringExtension());
        $this->twig->addExtension(new TwigUrlExtension());
        $this->twig->addExtension(new DebugExtension());
    }

    /*
     * PUBLIC METHODS
     */

    /**
     * Render a view template using Twig
     *
     * @param string $template  The template file
     * @param array $args  Associative array of data to display in the view (optional)
     * @return string
     */
    public function build(string $template, $args = []) : string
    {
        if ($this->twig->isDebug()) {
            $this->twig->addGlobal('globals', ['$_GET' => $_GET, '$_POST' => $_POST]);
        }
        return $this->twig->render($template, $args);
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /*
     * STATIC METHODS
     */
}
