<?php namespace mfe\server\libs\http\server\upgrades;

use mfe\server\api\http\IUpgradeServer;

/**
 * Class WebSocketServer
 *
 * @package mfe\server\libs\http\server\upgrades
 */
class WebSocketServer implements IUpgradeServer
{
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
