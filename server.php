<?php use mfe\server\libs\http\server\HttpServer;

use mfe\server\libs\http\server\middleware\ApplicationServer;
use mfe\server\libs\http\server\middleware\StaticServer;
use mfe\server\libs\http\server\Server;
use mfe\server\libs\http\server\upgrades\WebSocketServer;

require_once 'vendor/autoload.php';

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
