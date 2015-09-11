<?php namespace mfe\server\libs\http;

use mfe\server\api\http\IHttpSocketReader;
use mfe\server\api\http\IHttpSocketWriter;

/**
 * Class HttpSocketWriter
 *
 * @package mfe\server\libs\http
 */
class HttpSocketWriter implements IHttpSocketWriter
{
    const EOL = "\r\n";

    /** @var resource */
    private $socket;

    /** @var HttpSocketReader */
    private $reader;

    /**
     * @param resource $socket
     * @param IHttpSocketReader $reader
     */
    public function __construct($socket, IHttpSocketReader $reader)
    {
        $this->socket = $socket;
        $this->reader = $reader;
    }

    /**
     * @param $data
     *
     * @return bool
     */
    public function send($data)
    {
        $data = trim($data);

        $response =
            'HTTP/1.1 200 OK' . static::EOL .
            'Content-Type: text/html;charset=utf-8' . static::EOL .
            'Content-Length: ' . strlen($data) . static::EOL .
            'Connection: ' . ((!$this->reader->keepAlive) ? 'close' : 'keep-alive') .
            static::EOL . static::EOL .

            $data;

        fwrite($this->socket, $response);
    }
}
