<?php use mfe\server\libs\http\server\HttpServer;

use mfe\server\libs\http\server\middleware\ApplicationServer;
use mfe\server\libs\http\server\middleware\StaticServer;
use mfe\server\libs\http\server\upgrades\WebSocketServer;

use mfe\server\libs\http\server\StreamServer as Server;

require_once 'vendor/autoload.php';

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

$server = new Server(HttpServer::build([
    WebSocketServer::class
], [
    StaticServer::class,
    //ApplicationServer::class
]), $config = [
    'document_root' => __DIR__ . '/web',
    'document_index' => 'index.html',
    'application' => 'DefaultApplication'
]);

$server->listen('0.0.0.0:8000');
