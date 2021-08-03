<?php
declare(strict_types = 1);

namespace App\Helpers;

use Core\Application;
use Core\Router;
use function App\Libs\array_path_implode;
use function App\Libs\array_path_import;

class UrlHelper
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

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /*
     * STATIC METHODS
     */

    protected string $compareFunc;

    public function __construct()
    {
        $this->compareFunc = 'strcasecmp';
    }


    public function build(array $query, ?array $context = null, ?string $name = null) : string
    {
        $url = '/';

        if (empty($query)) {
            return $url;
        }

        $router = Application::current()->getRouter();

        $queryFull = array_fill_keys($router->getConfigurableParams(), null);
        $matchedRouteParams = array_intersect_ukey(@Application::current()->getRouter()->getMatchedRoute()['params'] ?? [], $queryFull, $this->compareFunc);
        $queryFull = array_replace($queryFull, $matchedRouteParams, $query);

        $targetRoute = null;

        if ($name !== null) {
            $routes = array_filter($router->getRoutes(), fn ($route) => $route['name'] === $name);
            $targetRoute = reset($routes) ?: null;
        }

        if ($targetRoute === null) {
            if ($context !== null) {
                $contextPath = array_path_import($context);
                $context = array_path_implode($router->getParameterPathSeparator(), $contextPath);
            }

            $params = @array_merge($context, $queryFull) ?? $queryFull;

            $bestScore = PHP_INT_MAX;

            foreach ($router->getRoutes() as $route) {
                // Check if all configured params in route are present in requested params
                $routeConfiguredParams = array_filter($route['params'], fn ($value) => $value !== null);
                $requestConfiguredParams = array_intersect_ukey($params, $routeConfiguredParams, $this->compareFunc);
                $differingConfiguredParams = array_merge(array_udiff_uassoc($routeConfiguredParams, $requestConfiguredParams, $this->compareFunc, $this->compareFunc), array_udiff_uassoc($requestConfiguredParams, $routeConfiguredParams, $this->compareFunc, $this->compareFunc));

                if (!empty($differingConfiguredParams)) {
                    continue;
                }

                // Check if no unconfigured params in route is missing
                $routeUnconfiguredParams = array_filter($route['params'], fn ($value) => $value === null);
                $requestMissingParams = array_diff_ukey($routeUnconfiguredParams, $params, $this->compareFunc);

                if (!empty($requestMissingParams)) {
                    continue;
                }

                // Count the number of unmentionned params in route that are requested.
                $requestUnconfiguredParams = array_diff_ukey($params, array_flip($router->getConfigurableParams()), $this->compareFunc);
                $requestAdditionalParams = array_diff_ukey($requestUnconfiguredParams, $routeUnconfiguredParams, $this->compareFunc);
                
                $score = count($requestAdditionalParams);
                
                if ($score < $bestScore) {
                    // Route with minimum of unmentionned params is the best route.
                    $bestScore = $score;
                    $targetRoute = $route;

                    // If all params are mentionned, can't be better.
                    if ($bestScore === 0) {
                        break;
                    }
                }
            }
        }

        if ($targetRoute !== null) {
            $url .= $targetRoute['route'] . $targetRoute['extension'];
            $queryString = [];

            foreach ($queryFull as $key => $value) {
                if (array_key_exists($key, $targetRoute['formats'])) {
                    $url = str_replace($targetRoute['formats'][$key], $this->sanitize((string)$value), $url);
                    unset($targetRoute['formats'][$key]);
                } elseif (!array_key_exists($key, $targetRoute['params'])) {
                    $queryString[$key] = $value;
                }
            }

            if ($context !== null) {
                foreach (@array_intersect_ukey($targetRoute['formats'], $context, $this->compareFunc) as $key => $value) {
                    $url = str_replace($targetRoute['formats'][$key], $this->sanitize((string)$context[$key]), $url);
                }
            }

            if (!empty($queryString)) {
                $url .= '?' . http_build_query($queryString);
            }
        }

        return $url;
    }

    protected function sanitize(?string $value) : string
    {
        if ($value === null) {
            return '';
        }

        $value = $this->stripAccent($value);
        $value = urlencode($value);
        $value = str_replace('+', '-', $value);
        return strtolower($value);
    }

    protected function stripAccent(string $str, string $charset = 'utf-8') : string
    {
        $str = htmlentities($str, ENT_QUOTES, $charset);

        $str = preg_replace('/&([a-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);/i', '$1', $str);
        $str = preg_replace('/&([a-z]{2})lig;/i', '$1', $str); // pour les ligatures e.g. '&oelig;'
        $str = preg_replace('/&[^;]+;/', '', $str);

        return $str;
    }
}
