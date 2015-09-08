<?php namespace mfe\server\libs\http\server;

use mfe\server\api\http\ITcpServer;

/**
 * Class HttpServer
 *
 * @package mfe\server\libs\http\server
 */
class HttpServer implements ITcpServer
{

    /**
     * @param array $upgrades
     * @param array $middleware
     *
     * @return ITcpServer
     */
    static public function build(array $upgrades = [], array $middleware = [])
    {
        return new static;
    }
}
