<?php namespace mfe\server\api\http;

/**
 * Interface IUpgradeServer
 *
 * @package mfe\server\api\http
 */
interface IUpgradeServer {
    /**
     * @param array $params
     *
     * @return static
     */
    static public function setup(array $params);

    /**
     * @param $socket
     *
     * @return bool
     */
    public function pipe($socket);
}
