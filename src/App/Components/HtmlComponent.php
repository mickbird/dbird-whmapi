<?php
declare(strict_types = 1);

namespace App\Components;

use Core\Component;
use Core\Controller;

/**
 * Class HtmlComponent provides utility methods to generate HTML content
 * @package App\Components
 */
class HtmlComponent extends Component
{
    /*
     * FIELDS
     */

    protected array $metas;
    protected array $stylesheets;
    protected array $scripts;

    /*
     * GETTERS / SETTERS
     */

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /**
     * HtmlComponent constructor.
     */
    public function __construct()
    {
        $this->metas = [];
        $this->stylesheets = [];
        $this->scripts = [];
    }

    /*
     * PUBLIC METHODS
     */

    /**
     * Add the specified meta to the view
     *
     * @param array|null $properties
     * @return $this HtmlComponent for method call chaining
     */
    public function meta(array $properties) : HtmlComponent
    {
        $this->metas[array_key_first($properties)] = $properties;
        return $this;
    }

    /**
     * Add the specified stylesheet to the view
     *
     * @param string $href
     * @param array|null $options
     * @return $this HtmlComponent for method call chaining
     */
    public function css(string $href, ?array $options = null) : HtmlComponent
    {
        $this->stylesheets[$href] = array_replace(['rel' => 'stylesheet', 'href' => $href], $options ?? []);
        return $this;
    }

    /**
     * Add the specified script to the view
     *
     * @param string $src
     * @param array|null $options
     * @return $this HtmlComponent for method call chaining
     */
    public function js(string $src, ?array $options = null) : HtmlComponent
    {
        $this->scripts[$src] = array_replace(['src' => $src], $options ?? []);
        return $this;
    }

    /**
     * Invoked before the controller call filter method.
     * @param Controller $controller The calling controller
     * @return bool True to continue processing. False to stop processing
     */
    public function beforeRender(Controller $controller) : void
    {
        parent::beforeRender($controller);

        $controller->set('metas', $this->metas);
        $controller->set('stylesheets', $this->stylesheets);
        $controller->set('scripts', $this->scripts);
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /*
     * STATIC METHODS
     */
}
