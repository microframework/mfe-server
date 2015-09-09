<?php namespace mfe\server\libs\http\server;

use mfe\server\libs\http\server\exceptions\StreamServerException;

/**
 * Trait TStreamServer
 *
 * @package mfe\server\libs\http\server
 */
trait TStreamServer
{
    /** @var resource */
    public $server;

    /** @var array */
    public $error = [
        'type' => 'NONE',
        'message' => 'No errors catch.'
    ];

    /** @var array */
    private $sockets = [];

    /**
     * @param string $ip
     * @param integer $port
     * @throws StreamServerException
     */
    protected function listenStreamServer($ip, $port)
    {
        $this->server = stream_socket_server(
            "tcp://{$ip}:{$port}",
            $this->error['type'],
            $this->error['message']
        );

        if (!$this->server) {
            throw new StreamServerException("{$this->error['type']} ({$this->error['message']})");
        }
    }

    protected function closeStreamServer()
    {
        fclose($this->server);
    }

    /**
     * @return void
     */
    protected function acceptSockets()
    {
        while (true) {
            $sockets = $this->sockets;
            $sockets[(int)$this->server] = $this->server;
            $write = $except = null;

            if (!stream_select($sockets, $write, $except, null)) {
                break;
            }

            if (in_array($this->server, $sockets, null)) {
                $socket = stream_socket_accept($this->server, -1);
                $this->sockets[(int)$socket] = $socket;
                unset($sockets[(int)$this->server]);
            }

            $this->processSockets($sockets);
        }
    }

    /**
     * @param array $sockets
     */
    protected function processSockets(array $sockets)
    {
        foreach ($sockets as $socket) {
            $this->handleSocket($socket);
        }
    }

    /**
     * @param resource $socket
     */
    protected function handleSocket($socket)
    {
        $this->closeSocket($socket);
    }

    /**
     * @param resource $socket
     */
    protected function closeSocket($socket)
    {
        unset($this->sockets[(int)$socket]);
        fclose($socket);
    }
}
