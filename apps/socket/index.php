<?php

require_once __DIR__.'/../../ext/Workerman/Autoloader.php';
require_once __DIR__.'/../../ext/Workerman/WebServer.php';
require_once __DIR__.'/../../ext/Workerman/Worker.php';

use Workerman\Worker;
// use Workerman\Lib\Timer;


ob_start();
// Create a Websocket server
$socket_worker = new Worker("websocket://0.0.0.0:2346");

// 4 processes
$socket_worker->count = 4;

$config = require('config.php');

ini_set('session.use_cookies', 0);


/* if ($config->debug ?? false) {
	Worker::$stdoutFile = __DIR__.'/workerman-stdout.log';
	Worker::$logFile = __DIR__.'/workerman-log.log';
} */


require(__DIR__.'/../../core/main.php');

core::run($config);

/* $socket_worker->onWorkerStart = function($worker) {
	foreach($worker->connections as $connection) {
		$connection->send(time());
	}
	echo "Worker starting...\n";
}; */

/* $socket_worker->onWorkerStop = function($worker) {
	echo "Worker starting...\n";
}; */

// Emitted when new connection come
$socket_worker->onConnect = function($connection) { // клиент соединился
	$connection->onWebSocketConnect = function($connection) {
		echo "New connection\n";
	};
};

/* Worker::$onMasterStop = function() {
	;
}; */

// Emitted when data received
$socket_worker->onMessage = function($connection, $msg) {
	// Send hello $data
	// $connection->send('echo: '.$msg);

	// Worker::$connections;

	$app = core::app();
	$app->dispatch($connection, $msg);
};

// Emitted when connection closed
$socket_worker->onClose = function($connection) { // клиент вышел
	echo "Connection closed\n";
};

// Run worker
Worker::runAll();

echo ob_get_clean();