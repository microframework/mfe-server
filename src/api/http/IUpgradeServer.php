<?php namespace mfe\server\api\http;
use ArrayObject;

/**
 * Interface IUpgradeServer
 *
 * @package mfe\server\api\http
 */
interface IUpgradeServer
{
    /**
     * @param resource $socket
     * @param IHttpSocketReader $reader
     * @param IHttpSocketWriter $writer
     *
     * @return bool
     */
    public function pipe($socket, IHttpSocketReader $reader, IHttpSocketWriter $writer);

    /**
     * @return static
     */
    public function closeSocket();

    /**
     * @param array $middleware
     *
     * @return static
     */
    public function registerMiddleware(array $middleware);

    /**
     * @param IHttpSocketReader $reader
     * @param ArrayObject $config
     *
     * @return bool|static
     */
    static public function tryUpgrade(IHttpSocketReader $reader, ArrayObject $config);

    /**
     * @return bool
     */
    public function isClose();
}
