<?php namespace mfe\server\api\http;
use ArrayObject;

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
     * @param array $upgrades
     * @param ArrayObject $config
     *
     * @return bool|IUpgradeServer
     */
    public function tryUpgrade(array $upgrades, ArrayObject $config);

    /**
     * @param string $response
     *
     * @return bool
     */
    public function upgradeWithResponse($response);

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
