<?php namespace mfe\server\libs\http\server;

use ArrayObject;
use mfe\server\api\http\ITcpServer;

/**
 * Class Server
 *
 * @package mfe\server\libs\http\server
 */
class Server
{
    /** @var ITcpServer */
    private $server;

    /** @var ArrayObject */
    private $config;

    /**
     * @param ITcpServer $server
     * @param array $config
     */
    public function __construct(ITcpServer $server, array $config)
    {
        $this->config = new ArrayObject($config, ArrayObject::ARRAY_AS_PROPS);
        $this->server = $server;
        $this->server->setConfig($this->config);
    }

    /**
     * @param $socketBind
     */
    public function listen($socketBind)
    {
        $bindAddress = explode(':', $socketBind);
        fwrite(STDOUT, "Server started at: http://{$socketBind}/" . PHP_EOL);
        $this->server->run($bindAddress[0], $bindAddress[1]);
    }
}
