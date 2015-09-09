<?php namespace mfe\server\libs\http\server;

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

    /**
     * @param ITcpServer $server
     */
    public function __construct(ITcpServer $server)
    {
        $this->server = $server;
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
