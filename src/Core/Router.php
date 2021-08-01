<?php
declare(strict_types = 1);

namespace Core;

use function App\Libs\convertToCamelCase;
use function App\Libs\convertToStudlyCaps;

/**
 * Router
 */
class Router
{
    /*
     * FIELDS
     */

    protected array $routes;
    protected array $matchedRoute;
    protected array $configurableParams;
    protected string $parameterPathSeparator;

    /*
     * GETTERS / SETTERS
     */

    /**
     * Get the list of connected routes
     * @return array
     */
    public function getRoutes() : array
    {
        return $this->routes;
    }

    /**
     * Get the route matched by dispatch
     * @return array
     */
    public function getMatchedRoute() : array
    {
        return $this->matchedRoute;
    }

    /**
     * Get the list of configurable params for the route
     * @return array|string[]
     */
    public function getConfigurableParams() : array
    {
        return $this->configurableParams;
    }

    /**
     * Get the separator used internaly when handling parameter path.
     * By default, path in parameter is defined using the "." character.
     * As regex cannot handle group name with ".", the separator path
     * character is used to when formating regex.
     * @return string
     */
    public function getParameterPathSeparator() : string
    {
        return $this->parameterPathSeparator;
    }

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /**
     * Router constructor.
     */
    public function __construct()
    {
        $this->routes = [];
        $this->matchedRoute = [];
        $this->configurableParams = ['plugin', 'controller', 'prefix', 'action', 'extension'];
        $this->parameterPathSeparator = '___';
    }

    /*
     * PUBLIC METHODS
     */

    /**
     * Connect a new route with specific name and params
     * @param string $name used to identify route
     * @param string $route template string
     * @param array $routeParams containing default params for that route
     * @return $this The router to allow call chaining
     */
    public function connect(string $name, string $route, array $routeParams = []) : Router
    {
        $extension = '{extension:(\.\w+)?}';
        $regex = $route . $extension;

        // Convert variables ({controller} and {controller:\w+}
        preg_match_all('/(?<Placeholders>\{(?<Params>[\w\.]+)(:(?<Arguments>[^}]+))?\})/i', $regex, $matches, PREG_UNMATCHED_AS_NULL);
        $matches = array_filter($matches, fn ($key) => is_string($key), ARRAY_FILTER_USE_KEY);

        $matches['Params'] = array_map(fn ($value) => str_replace('.', $this->parameterPathSeparator, $value), $matches['Params']);
        $matches['Arguments'] = array_map(fn ($value) => $value ?? '[a-z_-]+', $matches['Arguments']);

        // Convert the route to a regular expression: escape forward slashes
        $regex = preg_replace('/\//', '\\/', $regex);

        foreach ($matches['Placeholders'] as $key => $placeholder) {
            $regex = str_replace($placeholder, "(?<{$matches['Params'][$key]}>{$matches['Arguments'][$key]})", $regex);
        }

        // Add start and end delimiters, and case insensitive flag
        $regex = '/^' . $regex . '$/i';


        // Store values in registered routes
        $params = array_merge(array_fill_keys($this->getConfigurableParams(), null), array_fill_keys($matches['Params'], null), $routeParams);
        $formats = array_combine($matches['Params'], $matches['Placeholders']);

        $this->routes[] = [
            'name' => $name,
            'route' => $route,
            'extension' => $extension,
            'regex' => $regex,
            'params' => $params,
            'formats' => $formats
        ];

        return $this;
    }

    /**
     * Dispatch the route, creating the controller object and running the
     * action method
     *
     * @param string $url The route URL
     *
     * @return void
     * @throws \Exception
     */
    public function dispatch(string $url)
    {
        if (!$this->match($url)) {
            throw new \Exception('No route matched.', 404);
        }

        $request = new ServerRequest($this->matchedRoute['params']);
        $request->detectContentType();

        $response = new ServerResponse();
        $response->setContentType($request->getContentType());


        $controllerClass = $this->matchedRoute['params']['controller'];
        $controllerClass = convertToStudlyCaps($controllerClass);
        $controllerClass = $this->getNamespace() . '\\' . $controllerClass . 'Controller';

        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller class '{$controllerClass}' not found", 404);
        }

        $controllerInstance = new $controllerClass($request, $response);
        $controllerInstance->dispatch();
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /**
     * Match the route to the routes in the routing table, setting the $params
     * property if a route is found.
     * @param string $url The route URL
     * @return boolean  true if a match found, false otherwise
     */
    protected function match(string $url) : bool
    {
        $url = parse_url($url, PHP_URL_PATH);
        $url = trim($url, '/');
        
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $url, $matches, PREG_UNMATCHED_AS_NULL)) {
                $this->matchedRoute = $route;

                $matches = array_filter($matches, fn ($value, $key) => is_string($key) && !empty($value), ARRAY_FILTER_USE_BOTH);

                // Get named capture group values
                foreach ($matches as $key => $match) {
                    $this->matchedRoute['params'][$key] = $match;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Get the controller namespace by checking plugin params
     * @return string The request URL
     */
    protected function getNamespace() : string
    {
        $namespace = 'App\Controllers';

        if (($pluginNamespace = @$this->matchedRoute['params']['plugin']) !== null) {
            $namespace .= "\\{$pluginNamespace}";
        }

        return $namespace;
    }

    /*
     * STATIC METHODS
     */
}
