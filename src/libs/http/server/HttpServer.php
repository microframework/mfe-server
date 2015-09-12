<?php namespace mfe\server\libs\http\server;

use ArrayObject;
use mfe\server\api\http\IHttpSocketReader;
use mfe\server\api\http\IHttpSocketWriter;
use mfe\server\api\http\IMiddlewareServer;
use mfe\server\api\http\ITcpServer;
use mfe\server\api\http\IUpgradeServer;
use mfe\server\libs\http\HttpSocketReader;
use mfe\server\libs\http\HttpSocketWriter;

require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/autoload.php';

/**
 * Class HttpServer
 *
 * @package mfe\server\libs\http\server
 */
class HttpServer implements ITcpServer
{
    public $enableKeepAlive = false;

    /** @var ArrayObject */
    private $config;

    /** @var array */
    private $upgrades = [];

    /** @var array */
    private $middleware = [];

    /** @var IUpgradeServer[] */
    private $upgradedSockets = [];

    /** @var resource */
    private $socket;

    /**
     * @param $socket
     * @param array $proxy
     * @param ArrayObject $config
     */
    public function __construct($socket, array $proxy, ArrayObject $config)
    {
        $this->socket = $socket;
        $this->setConfig($config);
        $this->upgrades = $proxy['upgrades'];
        $this->middleware = $proxy['middleware'];
        $this->run();
    }

    /**
     * @param array $upgrades
     * @param array $middleware
     *
     * @return ITcpServer
     */
    static public function build(array $upgrades = [], array $middleware = [])
    {
        return [
            '_CLASS' => __CLASS__,
            'upgrades' => $upgrades,
            'middleware' => $middleware
        ];
    }

    /**
     * @param ArrayObject $config
     *
     * @return static
     */
    public function setConfig(ArrayObject $config)
    {
        $this->config = $config;
    }

    public function run()
    {
        $this->handleSocket($this->socket);
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

        if ($reader->keepAlive) {
            $this->keepAliveHandler($socket);
        }

        fclose($socket);

        return true;
    }

    /**
     * @param $socket
     * @param IHttpSocketReader|HttpSocketReader $reader
     * @param IHttpSocketWriter $writer
     *
     * @return bool
     */
    protected function pipe($socket, IHttpSocketReader $reader, IHttpSocketWriter $writer)
    {
        if ($upgrade = $reader->parseHeaders()->tryUpgrade($this->upgrades)) {
            $this->upgradedSockets[(int)$socket] = $upgrade;
        } else {
            if ($reader->isHttpRequest) {
                if ($this->enableKeepAlive) {
                    $reader->tryKeepAlive();
                }
                $reader->parseBody();
                $reader->overrideGlobals();
                $this->httpRequest($reader, $writer);
            }
        }

        return true;
    }

    /**
     * @param resource $socket
     */
    protected function keepAliveHandler($socket)
    {
        $keepAlive = true;

        while ($keepAlive && !feof($socket)) {
            $reader = new HttpSocketReader($socket);
            $writer = new HttpSocketWriter($socket, $reader);

            $this->pipe($socket, $reader, $writer);
            $keepAlive = $reader->keepAlive;
        }
    }

    /**
     * @param IHttpSocketReader|HttpSocketReader $reader
     * @param IHttpSocketWriter|HttpSocketWriter $writer
     *
     * @return void
     */
    protected function httpRequest(IHttpSocketReader $reader, IHttpSocketWriter $writer)
    {
        foreach ($this->middleware as $middleware) {
            $middleware = new $middleware($this->config);

            /** @var IMiddlewareServer $middleware */
            if ($middleware->request($reader, $writer)) {
                return;
            }
        }

        $writer->setHttpStatus(404)->send('Error 404: Not found file by path ' . $reader->getUriPath());
    }
}
