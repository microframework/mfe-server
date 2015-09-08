<?php namespace mfe\server\libs\http\server;

use mfe\server\api\http\ITcpServer;

/**
 * Class Server
 *
 * @package mfe\server\libs\http\server
 */
class Server {
    /**
     * @param ITcpServer $server
     */
    public function __construct(ITcpServer $server){

    }

    /**
     * @param $socketBind
     */
    public function listen($socketBind)
    {
    }
}
