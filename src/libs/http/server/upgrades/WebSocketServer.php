<?php namespace mfe\server\libs\http\server\upgrades;

use ArrayObject;
use mfe\server\api\http\IHttpSocketReader;
use mfe\server\api\http\IHttpSocketWriter;
use mfe\server\api\http\IUpgradeServer;
use mfe\server\libs\http\HttpSocketReader;
use mfe\server\libs\http\HttpSocketWriter;
use mfe\server\libs\http\server\helpers\TWebSocketHelper;

/**
 * Class WebSocketServer
 *
 * @package mfe\server\libs\http\server\upgrades
 */
class WebSocketServer implements IUpgradeServer
{
    const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    use TWebSocketHelper;

    /** @var ArrayObject */
    protected $config;

    /** @var array */
    protected $middleware = [];

    /** @var bool */
    protected $isFirstConnect = true;

    /** @var bool */
    private $socketClose = false;

    public function __construct(ArrayObject $config)
    {
        $this->config = $config;
    }

    /**
     * @param array $middleware
     *
     * @return static
     */
    public function registerMiddleware(array $middleware)
    {
        $this->middleware = $middleware;
        return $this;
    }

    /**
     * @param resource $socket
     * @param IHttpSocketReader|HttpSocketReader $reader
     * @param IHttpSocketWriter|HttpSocketWriter $writer
     *
     * @return bool
     */
    public function pipe($socket, IHttpSocketReader $reader, IHttpSocketWriter $writer)
    {
        if ('stream' === get_resource_type($socket)) {
            if ($this->isFirstConnect) {
                $this->isFirstConnect = false;
                $this->onSocketConnect($socket, $reader, $writer);
            } else {
                if ($this->socketClose) {
                    $this->onSocketClose($socket, $reader, $writer);
                    return;
                }
                if ('' !== $read = rtrim(fread($socket, 4096))) {
                    /** @var array $response */
                    $response = ($this->decode($read));
                    if ('close' === $response['type']) {
                        $this->socketClose = true;
                        return;
                    }
                    $this->onSocketRequest($socket, $reader, $writer);
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function isClose()
    {
        return (bool)$this->socketClose;
    }

    /**
     * @return static
     */
    public function closeSocket()
    {
        $this->socketClose = true;
        return $this;
    }

    /**
     * @param IHttpSocketReader|HttpSocketReader $reader
     * @param ArrayObject $config
     *
     * @return bool|static
     */
    static public function tryUpgrade(IHttpSocketReader $reader, ArrayObject $config)
    {
        if ($reader->hasHeader('Sec-WebSocket-Key')) {
            $SecWebSocketAccept = base64_encode(
                pack(
                    'H*', sha1($reader->getHeader('Sec-WebSocket-Key') . static::getGUID())
                )
            );
            $upgrade =
                'HTTP/1.1 101 Web Socket Protocol Handshake' . $reader::EOL .
                'Upgrade: websocket' . $reader::EOL .
                'Connection: Upgrade' . $reader::EOL .
                "Sec-WebSocket-Accept: {$SecWebSocketAccept}" . $reader::EOL . $reader::EOL;

            if ($reader->upgradeWithResponse($upgrade)) {
                $reader->isClose = false;
                return new static($config);
            }
        }
        return false;
    }

    /**
     * @return string
     */
    static private function getGUID()
    {
        return static::GUID;
    }

    /**
     * @param $socket
     * @param IHttpSocketReader|HttpSocketReader $reader
     * @param IHttpSocketWriter $writer
     */
    protected function onSocketConnect($socket, IHttpSocketReader $reader, IHttpSocketWriter $writer)
    {
        echo implode(':', $reader->getPeerInfo()) . ' присоеденился.' . PHP_EOL;
        fwrite($socket, $this->encode('Hello ' . implode(':', $reader->getPeerInfo())));
    }

    /**
     * @param $socket
     * @param IHttpSocketReader|HttpSocketReader $reader
     * @param IHttpSocketWriter $writer
     */
    protected function onSocketRequest($socket, IHttpSocketReader $reader, IHttpSocketWriter $writer)
    {
        fwrite($socket, $this->encode('-----------'));
    }

    /**
     * @param $socket
     * @param IHttpSocketReader|HttpSocketReader $reader
     * @param IHttpSocketWriter $writer
     */
    protected function onSocketClose($socket, IHttpSocketReader $reader, IHttpSocketWriter $writer)
    {
        echo implode(':', $reader->getPeerInfo()) . ' отвалился.' . PHP_EOL;
    }
}
