<?php namespace mfe\server\libs\http\server;

use ArrayObject;
use mfe\server\libs\http\server\exceptions\StreamServerException;

/**
 * Class StreamServer
 *
 * @package mfe\server\libs\http\server
 */
class StreamServer
{
    use TStreamServer;

    /** @var ArrayObject */
    private $config;

    /** @var array */
    private $builder;

    /**
     * @param string $builder
     * @param array $config
     */
    public function __construct($builder, array $config)
    {
        $this->config = new ArrayObject($config, ArrayObject::ARRAY_AS_PROPS);
        $this->builder = $builder;
    }

    /**
     * @param $socketBind
     *
     * @throws StreamServerException
     */
    public function listen($socketBind)
    {
        $bindAddress = explode(':', $socketBind);
        fwrite(STDOUT, "Server started at: http://{$socketBind}/" . PHP_EOL);

        $this->listenStreamServer($bindAddress[0], $bindAddress[1]);
        stream_set_timeout($this->server, 5);
        $this->acceptSockets();
        $this->closeStreamServer();
    }

    /**
     * @param resource $socket
     */
    protected function handleSocket($socket)
    {
        new $this->builder['_CLASS']($socket, [
            'upgrades' => $this->builder['upgrades'],
            'middleware' => $this->builder['middleware']
        ], $this->config);
    }
}
