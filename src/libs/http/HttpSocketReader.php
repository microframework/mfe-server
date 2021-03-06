<?php namespace mfe\server\libs\http;

use ArrayObject;
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

    public $isClose = true;
    public $keepAlive = false;
    public $isHttpRequest = false;

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

    /** @var null|array */
    private $parsedMultiform;

    /** @var array */
    private $contentType;

    /**
     * @param resource $socket
     */
    public function __construct($socket)
    {
        $this->socket = $socket;
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public function hasHeader($string)
    {
        return (array_key_exists($string, $this->headers)) ? true : false;
    }

    /**
     * @param string $string
     *
     * @return string|null
     */
    public function getHeader($string)
    {
        return $this->hasHeader($string) ? $this->headers[$string] : null;
    }

    /**
     * @return static
     */
    public function parseHeaders()
    {
        $headers = $this->readSocket(static::HEADER_LENGTH, true);



        foreach (explode(static::EOL, $headers) as $num => $line) {
            if ($num === 0 && '' !== $line && strpos($line, 'HTTP')) {
                $line = explode(' ', $line);
                $this->method = $line[0];
                $this->uri = parse_url($line[1]);
                $this->version = $line[2];

                $this->isHttpRequest = true;
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
     * @return bool
     */
    public function tryKeepAlive()
    {
        if (array_key_exists('Expect', $this->headers) && '100-continue' === $this->headers['Expect']) {
            $this->isClose = true;
            return $this->keepAlive = false;
        }
        if (array_key_exists('Connection', $this->headers) && 'keep-alive' === $this->headers['Connection']) {
            return $this->keepAlive = true;
        }
        $this->isClose = true;
        return false;
    }

    /**
     * @param array $upgrades
     * @param ArrayObject $config
     *
     * @return bool|IUpgradeServer
     */
    public function tryUpgrade(array $upgrades, ArrayObject $config = null)
    {
        foreach ($upgrades as $upgrade) {
            /** @var IUpgradeServer|bool $upgrade */
            if ($upgrade = $upgrade::tryUpgrade($this, $config)) {
                return $upgrade;
            }
        }
        return false;
    }

    /**
     * @param string $response
     *
     * @return bool
     */
    public function upgradeWithResponse($response)
    {
        try {
            fwrite($this->socket, $response);
            return true;
        } catch (HttpServerException $e) {
            return false;
        }
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

            if ('' !== $result = trim($result)) {
                $buffer .= $result . ($underCrLf ? static::EOL : null);
            }

            if ((is_bool($underCrLf) && !$underCrLf)
                || stream_get_meta_data($this->socket)['unread_bytes'] <= 0
            ) {
                break;
            }
        }

        return $buffer;
    }

    public function overrideGlobals()
    {
        $this->overrideSERVER();
        $this->overrideGET();
        $this->overridePOST();
        $this->overrideFILES();
        $this->overrideCOOKIE();
    }

    private function overrideSERVER()
    {
        $accept = $this->hasHeader('Accept') ? $this->getHeader('Accept') : null;
        $accept_charset = $this->hasHeader('Accept-Charset') ? $this->getHeader('Accept-Charset') : null;
        $accept_encoding = $this->hasHeader('Accept-Encoding') ? $this->getHeader('Accept-Encoding') : null;
        $connection = $this->hasHeader('Connection') ? $this->getHeader('Connection') : null;
        $referer = $this->hasHeader('Referer') ? $this->getHeader('Referer') : null;
        $host = $this->hasHeader('Host') ? $this->getHeader('Host') : null;
        $user_agent = $this->hasHeader('User-Agent') ? $this->getHeader('User-Agent') : null;

        $peerInfo = $this->getPeerInfo();

        $_SERVER = array_merge([
            'SERVER_PROTOCOL' => $this->version,
            'SERVER_SOFTWARE' => 'MfE Server',
            'REQUEST_METHOD' => $this->method,
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => '127.0.0.1',
            'REQUEST_TIME' => time(),
            'DOCUMENT_ROOT' => array_key_exists('DOCUMENT_ROOT', $_SERVER) ? $_SERVER['DOCUMENT_ROOT'] : __DIR__,
            'HTTP_ACCEPT' => $accept,
            'HTTP_ACCEPT_CHARSET' => $accept_charset,
            'HTTP_ACCEPT_ENCODING' => $accept_encoding,
            'HTTP_CONNECTION' => $connection,
            'HTTP_HOST' => $host,
            'HTTP_REFERER' => $referer,
            'HTTP_USER_AGENT' => $user_agent,
            'REMOTE_ADDR' => $peerInfo[0],
            'REMOTE_HOST' => $peerInfo[0],
            'REMOTE_PORT' => $peerInfo[1],
            'SCRIPT_FILENAME' => __FILE__,
            'SERVER_PORT' => 80,
            'SERVER_SIGNATURE' => 'MfE Server',
            'SCRIPT_NAME' => __FILE__,
            'REQUEST_URI' => $this->getFullURI()
        ], $this->headerNormalizeGlobals($this->headers));
    }

    private function headerNormalizeGlobals(array $array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
        }
        return $result;
    }

    private function overrideGET()
    {
        $_GET = [];
        if (array_key_exists('query', $this->uri)) {
            parse_str($this->uri['query'], $_GET);
        }
    }

    private function overridePOST()
    {
        if (array_key_exists('Content-Type', $this->headers) && $this->method === 'POST') {
            $this->contentType = explode(';', $this->headers['Content-Type']);

            switch ($this->contentType[0]) {
                case 'application/x-www-form-urlencoded':
                    if ('' !== $this->body) {
                        parse_str($this->body, $_POST);
                    }
                    break;
                case 'multipart/form-data':
                    $_POST = $this->parseMultiform($this->contentType)['_POST'];
                    break;
            }
        }
    }

    private function overrideFILES()
    {
        if ($this->contentType) {
            $_FILES = $this->parseMultiform($this->contentType)['_FILES'];
        }
    }

    private function overrideCOOKIE()
    {
        $_COOKIE = [];
        if (array_key_exists('Cookie', $this->headers)) {
            $cookies = explode('; ', $this->headers['Cookie']);
            foreach ($cookies as $cookie) {
                $cookie = explode('=', $cookie, 2);
                $_COOKIE[$cookie[0]] = $cookie[1];
            }
        }
    }

    /**
     * @param array $contentType
     *
     * @return array
     * @throws HttpServerException
     */
    private function parseMultiform(array $contentType)
    {
        if ($this->parsedMultiform) {
            return $this->parsedMultiform;
        }

        if (array_key_exists(1, $contentType) && 'multipart/form-data' === $contentType[0]) {
            $boundary = null;
            if ($boundary = explode('=', trim($contentType[1]))) {
                if (array_key_exists(1, $boundary)) {
                    $post = $files = [];
                    $boundary = $boundary[1];

                    if (substr($this->body, -strlen('--' . $boundary . static::EOL)) === '--' . $boundary . static::EOL) {
                        $this->body = substr($this->body, 0, -strlen('--' . $boundary . static::EOL));
                    }

                    $parts = explode('--' . $boundary . static::EOL, $this->body);

                    foreach ($parts as $part) {
                        $entity = new ArrayObject([
                            'headers' => [],
                            'value' => null,
                            'disposition' => null,
                            'type' => null
                        ], ArrayObject::ARRAY_AS_PROPS);

                        while (strlen($line = substr($part, 0, strpos($part, static::EOL))) > 0) {
                            $part = substr($part, strpos($part, static::EOL) + 2);

                            if (!strpos($line, ':')) {
                                continue;
                            }

                            $header_name = substr($line, 0, strpos($line, ':'));
                            $header_value = ltrim(substr($line, strpos($line, ':') + 1), ' ');

                            $entity->headers[] = ['name' => $header_name, 'value' => $header_value];
                        }

                        $entity->value = ltrim(substr($part, 0, -2), static::EOL);

                        foreach ($entity->headers as $header) {
                            if (strcasecmp($header['name'], 'Content-Disposition') === 0) {
                                $entity->disposition = $header['value'];
                            } else if (strcasecmp($header['name'], 'Content-Type')) {
                                $entity->type = $header['name'];
                            }
                        }

                        if (null !== $entity->disposition && substr($entity->disposition, strlen('form-data'), 1) === ';') {
                            $disposition_parameters = [];
                            foreach (array_map('trim', explode(';', substr($entity->disposition, strlen('form-data') + 1))) as $param) {
                                $param = explode('=', $param);
                                $disposition_parameters[$param[0]] = $param[1];
                            }
                            if (array_key_exists('filename', $disposition_parameters)) {
                                $tmp = tempnam(sys_get_temp_dir(), 'php_file');
                                $this->filesToDelete[] = $tmp;
                                file_put_contents($tmp, $entity->value);
                                $files[$disposition_parameters['name']] = [
                                    'name' => $disposition_parameters['filename'],
                                    'tmp_name' => $tmp,
                                    'size' => filesize($tmp),
                                    'type' => null !== $entity->type ? $entity->type : 'application/octet-stream'
                                ];
                            } else {
                                $post[$disposition_parameters['name']] = $entity->value;
                            }
                        }
                    }

                    return $this->parsedMultiform = [
                        '_POST' => $post,
                        '_FILES' => $files
                    ];
                }
                throw new HttpServerException('Not found Boundary in multipart/form-data');
            }
        }

        return $this->parsedMultiform = [
            '_POST' => [],
            '_FILES' => []
        ];
    }

    public function getURIPath()
    {
        return $this->uri['path'];
    }

    private function getFullURI()
    {
        return $this->uri['path'];
    }

    public function getPeerInfo()
    {
        return explode(':', stream_socket_get_name($this->socket, true));
    }
}
