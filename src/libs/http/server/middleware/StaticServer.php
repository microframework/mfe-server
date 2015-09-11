<?php namespace mfe\server\libs\http\server\middleware;

use ArrayObject;
use mfe\server\api\http\IHttpSocketReader;
use mfe\server\api\http\IHttpSocketWriter;
use mfe\server\api\http\IMiddlewareServer;

/**
 * Class StaticServer
 *
 * @package mfe\server\libs\http\server\middleware
 */
class StaticServer implements IMiddlewareServer
{
    private $document_root;
    private $document_index = 'index.html';

    public function __construct(ArrayObject $config)
    {
        if (isset($config->document_root)) {
            $this->document_root = str_replace('\\', '/', $config->document_root);
        }

        if (isset($config->document_index)) {
            $this->document_index = $config->document_index;
        }
    }

    /**
     * @param IHttpSocketReader $reader
     * @param IHttpSocketWriter $writer
     *
     * @return bool
     */
    public function request(IHttpSocketReader $reader, IHttpSocketWriter $writer)
    {
        $path = str_replace('..', '/', $reader->getUriPath());

        if ($path === '/') {
            $file = $this->document_root . '/' . $this->document_index;
        } else {
            $file = $this->document_root . $path;
        }

        if (file_exists($file) && is_readable($file) && !is_dir($file)) {
            $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file);
            $writer
                ->setHttpStatus(200)
                ->addHeader('Content-Type', $mimeType)
                ->send(file_get_contents($file), false);
            return true;
        }

        return false;
    }
}
