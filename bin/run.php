<?php
use Ratchet\Server\IoServer;
use WsPPServer\Service\PushPull;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

    require dirname(__DIR__) . '/vendor/autoload.php';

    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new PushPull()
            )
        ),
        8080
    );

    $server->run();