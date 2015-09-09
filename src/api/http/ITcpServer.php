<?php namespace mfe\server\api\http;

/**
 * Interface ITcpServer
 *
 * @package mfe\server\api\http
 */
interface ITcpServer
{
    /**
     * @param array $upgrades
     * @param array $middleware
     */
    static public function build(array $upgrades, array $middleware);

    /**
     * @param string $ip
     * @param integer $port
     */
    public function run($ip, $port);
}
