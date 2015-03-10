<?php
use Ratchet\Server\IoServer;
use WsPPServer\Service\PushPull;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

    require dirname(__DIR__) . '/vendor/autoload.php';

    $options = array('h' => '0.0.0.0', 'p' => '8080');
    $f_option = dirname(__DIR__) . '/config/local.php';
    if(file_exists($f_option)) {
    	$config =  include $f_option;
    	if(!empty($config['address'])) {
    		$options['h'] = $config['address'];
    	}
    	if(!empty($config['port'])) {
    		$options['p'] = $config['port'];
    	}
    } else {
    	$options = array_merge($options, getopt("h:p:"));
    }
    
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new PushPull()
            )
        ),
        $options['p'],
    	$options['h']
    );

    echo "Listen >>> " . $options['h'] . ":" . $options['p'] . "\n";
    $server->run();