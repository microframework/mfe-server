<?php namespace mfe\server\libs\http;

use mfe\server\api\http\IUpgradeServer;
use mfe\server\api\http\IHttpSocketReader;
use mfe\server\libs\http\server\exceptions\HttpServerException;

/**
 * Class HttpSocketReader
 *
 * @package mfe\server\libs\http
 */
class HttpSocketReader implements IHttpSocketReader
{
    const EOL = "\r\n";

    const HEADER_LENGTH = 4096;
    const PACKET_LENGTH = 1024;

    protected $method;
    protected $version;
    protected $uri;

    /** @var array */
    protected $headers = [];

    /** @var string */
    protected $body;

    /** @var resource */
    private $socket;

    private $filesToDelete = [];

    /**
     * @param resource $socket
     */
    public function __construct($socket)
    {
        $this->socket = $socket;
    }

    /**
     * @param $string
     *
     * @return bool
     */
    public function hasHeader($string)
    {
        return false;
    }

    /**
     * @return static
     */
    public function parseHeaders()
    {
        $headers = $this->readSocket(static::HEADER_LENGTH, true);

        foreach (explode(static::EOL, $headers) as $num => $line) {
            if ($num === 0) {
                $line = explode(' ', $line);
                $this->method = $line[0];
                $this->uri = parse_url($line[1]);
                $this->version = $line[2];
            } elseif (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $this->headers[$matches[1]] = $matches[2];
            }
        }
        return $this;
    }

    /**
     * @return static
     */
    public function parseBody()
    {
        if (array_key_exists('Content-Length', $this->headers)) {
            $this->body = $this->readSocket(static::PACKET_LENGTH);
        }
        return $this;
    }


    /**
     * @param $upgrades
     *
     * @return bool|IUpgradeServer
     */
    public function tryUpgrade($upgrades)
    {
        return false;
    }

    /**
     * @param int $limit
     * @param bool $underCrLf
     *
     * @return string
     */
    private function readSocket($limit, $underCrLf = null)
    {
        $buffer = '';

        $f_read = 'fread';

        if ($underCrLf) {
            $f_read = 'fgets';
        }

        while (!feof($this->socket) && '' !== strlen($result = $f_read($this->socket, $limit))) {
            if ($underCrLf && static::EOL === $result) {
                $underCrLf = false;
            }

            $buffer .= rtrim($result) . ($underCrLf ? static::EOL : null);
            if ((is_bool($underCrLf) && !$underCrLf)
                || stream_get_meta_data($this->socket)['unread_bytes'] <= 0
            ) {
                break;
            }
        }

        return $buffer;
    }
}
