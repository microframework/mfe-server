<?php namespace mfe\server\libs\http\server\middleware;

use mfe\server\api\http\IMiddlewareServer;

/**
 * Class StaticServer
 *
 * @package mfe\server\libs\http\server\middleware
 */
class StaticServer implements IMiddlewareServer {
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
