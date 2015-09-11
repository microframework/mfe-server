<?php namespace mfe\server\api\http;

/**
 * Interface IHttpSocketReader
 *
 * @package mfe\server\api\http
 */
interface IHttpSocketReader
{
    /**
     * @return static
     */
    public function parseHeaders();

    /**
     * @return static
     */
    public function parseBody();

    /**
     * @param $upgrades
     *
     * @return IUpgradeServer
     */
    public function tryUpgrade($upgrades);

    /**
     * @param string $string
     *
     * @return bool
     */
    public function hasHeader($string);

    /**
     * @return void
     */
    public function overrideGlobals();

    /**
     * @param string $string
     *
     * @return string
     */
    public function getHeader($string);

    /**
     * @return string
     */
    public function getUriPath();
}
