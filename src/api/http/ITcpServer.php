<?php namespace mfe\server\api\http;
use ArrayObject;
use Thread;

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
     * @return void
     */
    public function run();

    /**
     * @param ArrayObject $config
     *
     * @return static
     */
    public function setConfig(ArrayObject $config);
}
