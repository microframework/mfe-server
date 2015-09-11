<?php namespace mfe\server\api\http;

/**
 * Interface IHttpSocketWriter
 *
 * @package mfe\server\api\http
 */
interface IHttpSocketWriter
{
    /**
     * @param $emitter
     *
     * @return void|bool
     */
    public function send($emitter);

    /**
     * @param integer $statusCode
     *
     * @return static
     */
    public function setHttpStatus($statusCode);

    /**
     * @param string $name
     * @param string $value
     *
     * @return static
     */
    public function addHeader($name, $value);
}
