<?php
declare(strict_types = 1);

namespace Core;

use function App\Libs\array_path_export;
use function App\Libs\array_path_import;
use function App\Libs\array_path_sort_keys;

class ServerRequest
{
    /*
     * FIELDS
     */

    protected array $routeParams;
    protected array $paramsProviders;

    protected array $supportedContentTypes;
    protected string $contentType;

    /*
     * GETTERS / SETTERS
     */

    /**
     * Get the HTTP method used by the request (in lower case)
     * This is a shortcut method on $_SERVER
     * @return string
     */
    public function getMethod() : string
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Get the HTTP host
     * This is a shortcut method on $_SERVER
     * @return string
     */
    public function getHost() : string
    {
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * Get the current URL
     * This is a shortcut method on $_SERVER
     * @return string
     */
    public function getUrl() : string
    {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Get the content type detected for the request.
     *
     * @return string
     */
    public function getContentType() : string
    {
        return $this->contentType;
    }

    /**
     * Get the requested controller name (without 'Controller' suffix).
     * This is a shortcut method on getParam
     * @return string
     */
    public function getController() : string
    {
        return $this->getParam('controller');
    }

    /**
     * Get the requested prefix name for the action.
     * This is a shortcut method on getParam
     * @return string|null
     */
    public function getPrefix() : ?string
    {
        return $this->getParam('prefix');
    }

    /**
     * Get the requested action name (without 'Action' suffix)
     * This is a shortcut method on getParam
     * @return string
     */
    public function getAction() : string
    {
        return $this->getParam('action');
    }

    /**
     * Get the requested extension (prefixed by the dot)
     * This is a shortcut method on getParam
     * @return string
     */
    public function getExtension() : ?string
    {
        return $this->getParam('extension');
    }

    /**
     * Indicate if the current request is of type get
     * This is a shortcut method on is
     * @return bool
     */
    public function isGet() : bool
    {
        return $this->is('get');
    }

    /**
     * Indicate if the current request is of type post
     * This is a shortcut method on is
     * @return bool
     */
    public function isPost() : bool
    {
        return $this->is('post');
    }

    /**
     * Indicate if the current request is of type ajax
     * This is a shortcut method on is
     * @return bool
     */
    public function isAjax() : bool
    {
        return $this->is('ajax');
    }

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /**
     * ServerRequest constructor.
     * @param array $routeParams
     */
    public function __construct(array $routeParams)
    {
        $this->routeParams = $routeParams;
        $this->paramsProviders = [
            'GET' => fn () => $_GET,
            'POST' => fn () => $_POST,
            'FILES' => function () {
                $files = array_path_import($_FILES);
                $files = array_path_sort_keys([0, 2, 1], $files);
                $files = array_path_export($files);

                return $files;
            },
            'ROUTE' => fn () => $this->routeParams
        ];

        $this->supportedContentTypes = [
            'text/plain' => ['.txt'],
            'text/html' => ['.html', '.htm'],
            'application/json' => ['.json'],
            'application/xml' => ['.xml'],
            'application/pdf' => ['.pdf']
        ];
    }

    /*
     * PUBLIC METHODS
     */

    /**
     * Detect the content type of the request by using detectors
     */
    public function detectContentType()
    {
        foreach ($this->getContentTypeDetector() as $detector) {
            $contentType = $detector();

            if ($contentType === false) {
                break;
            }

            if (($contentType) !== null) {
                $this->contentType = $contentType;
                return;
            }
        }

        $this->contentType = 'text/plain';
    }

    /**
     * Get the parameters available in global variables (GET, POST, FILES and ROUTE).
     * @param string|null ...$containers Specify from which containers (GET, POST, FILES or ROUTE) the parameters should be returned.
     * @return array
     */
    public function getParams(?string ...$containers) : array
    {
        $containers = $this->enumerateParamContainers($containers);
        return call_user_func_array('array_replace_recursive', $containers);
    }

    /**
     * Get the parameter specified by $key available in global variables (GET, POST, FILES or ROUTE).
     *
     * @param string|null ...$containers Specify from which containers (GET, POST, FILES or ROUTE) the parameter should be returned.
     * @return array
     */
    public function getParam(string $key, ?string ...$containers) : ?string
    {
        $containers = $this->enumerateParamContainers($containers);

        foreach ($containers as $container) {
            if (array_key_exists($key, $container)) {
                return $container[$key];
            }
        }

        return null;
    }

    /**
     * Indicate if the current request is of type $type (get, post, ajax)
     *
     * @param string $type
     * @return bool
     */
    public function is(string $type) : bool
    {
        switch (strtolower($type)) {
            case 'get':
                return strcasecmp(@$this->getMethod() ?? '', 'GET') === 0;

            case 'post':
                return strcasecmp(@$this->getMethod() ?? '', 'POST') === 0;

            case 'ajax':
                return strcasecmp(@$_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest') === 0;

            default:
                return false;
        }
    }

    /**
     * Get a boolean indicating if the current request match all types $types (get, post, ajax)
     *
     * @param string ...$types
     * @return bool
     */
    public function isAll(string ...$types) : bool
    {
        foreach ($types as $type) {
            if (!$this->is($type)) {
                return false;
            }
        }

        return true;
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /**
     * Get the content of each $container as a nested array
     * @param array|null $containers
     * @return array
     */
    protected function enumerateParamContainers(?array $containers) : array
    {
        $providers = $this->paramsProviders;

        if (!empty($containers)) {
            $providers = array_intersect_ukey($providers, array_flip($containers), 'strcasecmp');
        }

        return array_map(fn ($c) => $c(), $providers);
    }

    /**
     * Get the detectors used to detect request content type
     * The following detector are used :
     *  - URL extension
     *  - HTTP header CONTENT_TYPE
     *  - HTTP header HTTP_ACCEPT
     * @return \Closure[]
     */
    protected function getContentTypeDetector() : array
    {
        // Detector for extension
        $contentTypeDetectorByExtension = function () {
            $extension = @$this->routeParams['extension'];

            if ($extension === null) {
                return null;
            }

            foreach ($this->supportedContentTypes as $contentType => $extensions) {
                if (in_array($extension, $extensions)) {
                    return $contentType;
                }
            }

            return false;
        };

        // Detector for HTTP header CONTENT_TYPE
        $contentTypeDetectorByHeaderContentType = function () : ?string {
            return @$_SERVER['CONTENT_TYPE'];
        };

        // Detector for HTTP header HTTP_ACCEPT
        $contentTypeDetectorByHeaderAccept = function () : ?string {
            $contentTypes = explode(',', @$_SERVER['HTTP_ACCEPT']);

            foreach ($contentTypes as $contentType) {
                if (array_key_exists($contentType, $this->supportedContentTypes)) {
                    return $contentType;
                }
            }

            return null;
        };

        return [
            $contentTypeDetectorByExtension,
            $contentTypeDetectorByHeaderContentType,
            $contentTypeDetectorByHeaderAccept
        ];
    }

    /*
     * STATIC METHODS
     */
}
