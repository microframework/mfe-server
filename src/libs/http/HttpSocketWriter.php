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

    /** @var int */
    private $statusCode = 200;

    /** @var resource */
    private $socket;

    /** @var HttpSocketReader */
    private $reader;

    private $headers = [
        'Content-Type' => 'text/html;charset=utf-8'
    ];

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
     * @param bool $trim
     *
     * @return bool
     */
    public function send($data, $trim = true)
    {
        if($trim){
            $data = trim($data);
        }

        $response =
            'HTTP/1.1 ' . $this->getHttpStatus(true) . static::EOL .
            $this->getHeaders() .
            'Content-Length: ' . strlen($data) . static::EOL .
            'Connection: ' . ((!$this->reader->keepAlive) ? 'close' : 'keep-alive') .
            static::EOL . static::EOL .

            $data;

        fwrite($this->socket, $response);
    }

    /**
     * @param integer $statusCode
     *
     * @return static
     */
    public function setHttpStatus($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getHttpStatus($withPhrase = false)
    {
        return (string)$this->statusCode . ($withPhrase ? ' ' . static::$phrases[$this->statusCode] : null);
    }

    /**
     * @param $name
     * @param $value
     *
     * @return static
     */
    public function addHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    private function getHeaders()
    {
        $headers = '';
        foreach ($this->headers as $name => $value) {
            $headers .= $name . ': ' . $value . static::EOL;
        }
        return $headers;
    }

    static private $phrases = [
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];
}
