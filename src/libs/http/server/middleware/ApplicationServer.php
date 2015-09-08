<?php namespace mfe\server\libs\http\server\middleware;

use mfe\server\api\http\IMiddlewareServer;

/**
 * Class ApplicationServer
 *
 * @package mfe\server\libs\http\server\middleware
 */
class ApplicationServer implements IMiddlewareServer {
    /**
     * @param array $params
     *
     * @return static
     */
    static public function setup(array $params)
    {
        return new static;
    }
}
