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
}
