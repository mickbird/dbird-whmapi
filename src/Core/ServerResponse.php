<?php
declare(strict_types = 1);

namespace Core;

use MongoDB\Driver\Server;

class ServerResponse
{
    /*
     * FIELDS
     */

    protected int $statusCode;

    protected array $headers;
    protected ?string $body;

    /*
     * GETTERS / SETTERS
     */

    /**
     * Get the content type of the response
     * @return string
     */
    public function getContentType() : string
    {
        return $this->getHeader('Content-Type');
    }

    /**
     * Set the content type of the response
     * @param string $contentType
     * @return $this for method call chaining
     */
    public function setContentType(string $contentType) : ServerResponse
    {
        $this->setHeader('Content-Type', $contentType);
        return $this;
    }

    /**
     * Get the location header of the response
     * @return string|null
     */
    public function getLocation() : ?string
    {
        return $this->getHeader('Location');
    }

    /**
     * Set the location header of the response
     * @param string|null $location
     * @return $this for method call chaining
     */
    public function setLocation(?string $location) : ServerResponse
    {
        $this->setHeader('Location', $location);
        return $this;
    }

    /**
     * Indicate whatever a location header as been set.
     * @return bool
     */
    public function hasLocation() : bool
    {
        return $this->getLocation() !== null;
    }

    /**
     * Get the status code of the response
     * @return int
     */
    public function getStatusCode() : int
    {
        return $this->statusCode;
    }

    /**
     * Set the status code of the response
     * @param int $statusCode
     * @return $this for method call chaining
     */
    public function setStatusCode(int $statusCode) : ServerResponse
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Get the body of the response
     * @return string|null
     */
    public function getBody() : ?string
    {
        return $this->body;
    }

    /**
     * Set the body of the response
     * @param string|null $body
     * @return $this for method call chaining
     */
    public function setBody(?string $body) : ServerResponse
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Indicate whatever a body has been set
     * @return bool
     */
    public function hasBody() : bool
    {
        return $this->body !== null;
    }

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /**
     * ServerResponse constructor.
     */
    public function __construct()
    {
        $this->statusCode = 200;
        $this->headers = [];
        $this->body = null;
    }

    /*
     * PUBLIC METHODS
     */

    /**
     * Return the headers and body to the client
     */
    public function send()
    {
        foreach ($this->headers as $key => $value) {
            $header = $value !== null ? "{$key}: {$value}" : $key;
            header($header);
        }

        http_response_code($this->statusCode);

        if ($this->hasBody()) {
            echo($this->getBody());
        }
    }


    /**
     * Get the value of the header by $key
     * @param string $key
     * @return string|null
     */
    public function getHeader(string $key) : ?string
    {
        return @$this->headers[$key];
    }

    /**
     * Set the value of the header by $key
     * @param string $key
     * @param string|null $value
     * @return $this for method call chaining
     */
    public function setHeader(string $key, ?string $value = null) : ServerResponse
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Remove the value of the header by $key
     * @param string $key
     */
    public function clearHeader(string $key)
    {
        $this->setHeader($key, null);
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /*
     * STATIC METHODS
     */
}
