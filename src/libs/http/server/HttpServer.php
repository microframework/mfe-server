<?php namespace mfe\server\libs\http\server;

use mfe\server\api\http\IHttpSocketReader;
use mfe\server\api\http\IHttpSocketWriter;
use mfe\server\api\http\ITcpServer;
use mfe\server\api\http\IUpgradeServer;
use mfe\server\libs\http\HttpSocketReader;
use mfe\server\libs\http\HttpSocketWriter;

/**
 * Class HttpServer
 *
 * @package mfe\server\libs\http\server
 */
class HttpServer implements ITcpServer
{
    use TStreamServer;

    /** @var array */
    private $upgrades = [];

    /** @var array */
    private $middleware = [];

    /** @var IUpgradeServer[] */
    private $upgradedSockets = [];

    public function __construct($upgrades, $middleware)
    {
        $this->upgrades = $upgrades;
        $this->middleware = $middleware;
    }

    /**
     * @param array $upgrades
     * @param array $middleware
     *
     * @return ITcpServer
     */
    static public function build(array $upgrades = [], array $middleware = [])
    {
        return new static($upgrades, $middleware);
    }

    public function run($ip, $port)
    {
        $this->listenStreamServer($ip, $port);
        //stream_set_chunk_size($this->server, 1024);
        stream_set_blocking($this->server, 0);
        stream_set_timeout($this->server, 5);
        $this->acceptSockets();
        $this->closeStreamServer();
    }

    /**
     * @param resource $socket
     *
     * @return bool
     */
    protected function handleSocket($socket)
    {
        $reader = new HttpSocketReader($socket);
        $writer = new HttpSocketWriter($socket, $reader);

        $upgrade = null;

        if ([] !== $this->upgrades && array_key_exists((int)$socket, $this->upgradedSockets)) {
            $upgrade = $this->upgradedSockets[(int)$socket];
            return $upgrade->pipe($socket, $reader, $writer);
        }

        $this->pipe($socket, $reader, $writer);
        $this->closeSocket($socket);

        return true;
    }

    /**
     * @param $socket
     * @param IHttpSocketReader $reader
     * @param IHttpSocketWriter $writer
     *
     * @return bool
     */
    protected function pipe($socket, IHttpSocketReader $reader, IHttpSocketWriter $writer)
    {
        if ($upgrade = $reader->parseHeaders()->tryUpgrade($this->upgrades)) {
            $this->upgradedSockets[(int)$socket] = $upgrade;
        } else {
            $reader->parseBody();
            $this->httpRequest($socket, $reader, $writer);

            if ($reader->hasHeader('Keep-Alive')) {
                $this->keepAliveHandler($socket);
            }
        }
        return true;
    }

    protected function keepAliveHandler()
    {
    }

    /**
     * @param resource $socket
     * @param IHttpSocketReader $reader
     * @param IHttpSocketWriter $writer
     *
     * @return void
     */
    private function httpRequest($socket, IHttpSocketReader $reader, IHttpSocketWriter $writer)
    {
        $data = 'Hello World';
        $writer->send($data);
    }
}
