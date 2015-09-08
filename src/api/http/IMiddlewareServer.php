<?php namespace mfe\server\api\http;

/**
 * Interface IMiddlewareServer
 *
 * @package mfe\server\api\http
 */
interface IMiddlewareServer {
    /**
     * @param array $params
     *
     * @return static
     */
    static public function setup(array $params);
}
