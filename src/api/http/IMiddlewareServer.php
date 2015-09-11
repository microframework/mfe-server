<?php namespace mfe\server\api\http;

/**
 * Interface IMiddlewareServer
 *
 * @package mfe\server\api\http
 */
interface IMiddlewareServer
{
    /**
     * @param IHttpSocketReader $reader
     * @param IHttpSocketWriter $writer
     *
     * @return bool
     */
    public function request(IHttpSocketReader $reader, IHttpSocketWriter $writer);
}
