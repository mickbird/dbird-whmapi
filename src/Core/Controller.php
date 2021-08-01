<?php
declare(strict_types = 1);

namespace Core;

use App\Helpers\UrlHelper;
use ReflectionException;
use ReflectionMethod;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Html2Pdf;
use function App\Libs\array_remove;
use function App\Libs\array_filter_recursive;
use function App\Libs\convertToCamelCase;
use function App\Libs\xml_encode;

/**
 * Class Controller provides controller functionnality.
 * @package Core
 */
abstract class Controller
{
    /*
     * FIELDS
     */

    private ServerRequest $request;
    private ServerResponse $response;

    private UrlHelper $url;

    private array $components;
    private array $view;

    private bool $autoRender;

    /*
     * GETTERS / SETTERS
     */

    /**
     * Get the request object
     * @return \Core\ServerRequest
     */
    public function getRequest() : ServerRequest
    {
        return $this->request;
    }

    /**
     * Get the response object
     * @return \Core\ServerResponse
     */
    public function getResponse() : ServerResponse
    {
        return $this->response;
    }

    /**
     * Return the component registered on this controller
     * @param string $componentName
     */
    public function getComponent(string $componentName)
    {
        return @$this->components[$componentName];
    }

    /**
     * Get the URL helper instance
     * @return UrlHelper
     */
    public function url() : UrlHelper
    {
        return $this->url;
    }

    /**
     * Disable automatic call to render method
     */
    public function disableAutorender() : void
    {
        $this->autoRender = false;
    }

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /**
     * Controller constructor.
     * @param ServerRequest $request
     * @param ServerResponse $response
     */
    public function __construct(ServerRequest $request, ServerResponse $response)
    {
        $this->request = $request;
        $this->response = $response;

        $this->url = new UrlHelper();

        $this->components = [];

        $this->view = [];
        $this->autoRender = true;
    }

    /**
     * Initialize the controller. Acts like a lightweight constructor
     */
    protected function initialize()
    {
    }

    /*
     * PUBLIC METHODS
     */

    /**
     * Get the value specified by $key from the view container
     * @param string $key
     * @return ?string
     */
    public function get(string $key) : ?string
    {
        return @$this->view[$key];
    }


    /**
     * Set a value specified $key in the view container
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value) : void
    {
        $this->view[$key] = $value;
    }


    /**
     * Register a new component on this controller
     * @param Component $component
     * @return void
     */
    public function registerComponent(Component $component) : void
    {
        $key = get_class($component);
        $this->components[$key] = $component;
    }


    /**
     * Dispatch the processing of the request lifecycle using extended functionalities
     */
    public function dispatch() : void
    {
        $this->initialize();

        if ($this->beforeFilter() !== false) {
            $this->filter();
        }

        if (!$this->getResponse()->hasLocation() && $this->autoRender) {
            $this->render();
        }

        $this->afterFilter();

        $this->getResponse()->send();
    }

    /**
     * Add a redirect (header location) to the response for the specified URL.
     * If $url is a String, it's treated as well formatted URL.
     * If $url is an array, it's passed to URL Helper to create URL string based on routes.
     * @param string|array $url String or Array of the destination.
     * @param ?int $status added to the response.
     * @return bool True if success, otherwise false
     */
    public function redirect($url, ?int $status = null) : bool
    {
        $url ??= [];
        $status ??= 302;

        if ($this->beforeRedirect($url) === false) {
            return false;
        }

        // Handle url rewriting
        if (is_array($url)) {
            $url = $this->url->build($url);
        }

        $this->getResponse()
            ->setLocation($url)
            ->setStatusCode($status);

        return true;
    }

    /**
     * Add a redirect (header location) to the response for the current URL
     * @param ?int $status added to the response.
     * @return bool True if success, otherwise false
     */
    public function redirectSelf(?int $status = null) : bool
    {
        return $this->redirect($this->getRequest()->getUrl(), $status);
    }

    /**
     * Add a redirect (header location) to the response for the home page URL
     * @param ?int $status added to the response.
     * @return bool True if success, otherwise false
     */
    public function redirectHome(?int $status = null) : bool
    {
        return $this->redirect([], $status);
    }

    /**
     * Add a redirect (header location) to the response and set the "coming from" url to current URL (save).
     * @param string|array $url String or Array of the destination.
     * @param ?int $status added to the response.
     * @return bool True if success, otherwise false
     */
    public function redirectFrom($url, ?int $status = null) : bool
    {
        $_SESSION['redirectFrom'] = $this->getRequest()->getUrl();
        return $this->redirect($url, $status);
    }

    /**
     * Add a redirect (header location) to the response from the "coming from" url (restore).
     * Use $url if the "coming from" url is not set.
     * @param string|array $url String or Array of the destination.
     * @param ?int $status added to the response.
     * @return bool True if success, otherwise false
     */
    public function redirectBack($url = null, ?int $status = null) : bool
    {
        $url = array_remove($_SESSION, 'redirectFrom') ?? $url;
        return $this->redirect($url, $status);
    }


    /**
     * Save the state of $data in $_SESSION
     * @param array $data
     * @param bool $secure indicate whatever keys starting with _ should be skipped. True by default
     * @return void
     */
    public function saveState(array $data, bool $secure = true) : void
    {
        $params = $this->getRequest()->getParams();
        $key = implode('_', [$params['controller'], $params['action']]);

        if ($secure) {
            $data = array_filter_recursive($data, fn ($key) => substr((string)$key, 0, 1) !== '_', ARRAY_FILTER_USE_KEY);
        }

        if (!empty($data)) {
            $_SESSION['savedState'] ??= [];
            $_SESSION['savedState'][$key] = $data;
        }
    }

    /**
     * Restore the previously saved state in $data
     * @param array $data
     * @return bool true if success, otherwise false
     */
    public function restoreState(array &$data) : bool
    {
        $params = $this->getRequest()->getParams();
        $key = implode('_', [$params['controller'], $params['action']]);

        if (@$_SESSION['savedState'][$key] === null) {
            return false;
        }

        $savedState = array_remove($_SESSION['savedState'], $key);

        $data = array_replace_recursive($data, $savedState);

        /*foreach ($savedState as $key => $value) {
            $data[$key] = $value;
        }*/

        return true;
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /**
     * Invoke the action specified by $name
     *
     * @return void
     * @throws Exception
     */
    protected function filter() : void
    {
        $methodName = implode('-', [@$this->getRequest()->getPrefix(), $this->getRequest()->getAction(), 'Action']);
        $methodName = convertToCamelCase($methodName);

        try {
            // Get an array containing parameters name as key and null as value
            $method = new ReflectionMethod($this, $methodName);
            $methodParams = $method->getParameters();
            $methodParams = array_merge(...array_map(fn($x) => [$x->getName() => $x->isOptional() ? $x->getDefaultValue() : null], $methodParams));
            $methodParams = array_change_key_case($methodParams, CASE_LOWER);

            // Fill $methodParams values with $_GET and $routeParams
            $methodParams = array_replace($methodParams, array_change_key_case(array_intersect_ukey($this->getRequest()->getParams('get', 'route'), $methodParams, 'strcasecmp')));

            $method->invokeArgs($this, $methodParams);
        } catch (ReflectionException $ex) {
            throw new \Exception("Method '{$methodName}' not found in controller '{$this->getRequest()->getController()}'", 404);
        }
    }

    /**
     * Invoked before the controller call filter method.
     * @return bool True to continue processing. False to stop processing
     */
    protected function beforeFilter() : bool
    {
        foreach ($this->components as $component) {
            if ($component->beforeFilter($this) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * After filter - called after an action method.
     *
     * @return void
     */
    protected function afterFilter() : void
    {
    }


    /**
     * Invoked before the controller call render method.
     * @return void
     */
    protected function beforeRender() : void
    {
        foreach ($this->components as $component) {
            $component->beforeRender($this);
        }
    }

    /**
     * Render the view according to request content type.
     * @return True if a rendering has been made. Otherwise false
     */
    protected function render() : bool
    {
        switch ($this->getResponse()->getContentType()) {
            case 'text/html':
                $this->renderHtml();
                return true;

            case 'application/json':
                $this->renderJson();
                return true;

            case 'application/xml':
                $this->renderXml();
                return true;

            default:
                return false;
        }
    }

    /**
     * Render the view as HTML
     * @param ?string $template Path to the template file used to render view.
     * @return void
     */
    protected function renderHtml(?string $template = null) : void
    {
        $this->autoRender = false;

        if ($template === null) {
            $params = $this->getRequest()->getParams();

            $controller = ucfirst($params['controller']);
            $actionName = implode('-', [@$this->getRequest()->getPrefix(), $this->getRequest()->getAction()]);
            $actionName = convertToCamelCase($actionName);
            $template = "/{$controller}/{$actionName}.html.twig";
        }

        $this->beforeRender();

        $this->getResponse()->setContentType('text/html');
        $this->getResponse()->setBody(Application::current()->getViewBuilder()->build($template, $this->view));
    }

    /**
     * Render the view as JSON
     * @return void
     */
    protected function renderJson() : void
    {
        $this->autoRender = false;

        $this->beforeRender();

        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setBody(json_encode($this->view, JSON_PRETTY_PRINT));
    }

    /**
     * Render the view as XML
     * @return void
     */
    protected function renderXml() : void
    {
        $this->autoRender = false;

        $this->beforeRender();

        $this->getResponse()->setContentType('application/xml');
        $this->getResponse()->setBody(xml_encode($this->view));
    }

    /**
     * Invoked before the controller call redirect method.
     * @param  mixed $url
     * @return bool True to continue processing. False to stop processing
     */
    protected function beforeRedirect($url) : bool
    {
        foreach ($this->components as $component) {
            if ($component->beforeRedirect($this, $url) === false) {
                return false;
            }
        }

        return true;
    }

    /*
     * STATIC METHODS
     */
}
