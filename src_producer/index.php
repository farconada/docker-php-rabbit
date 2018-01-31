<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
$connection = new AMQPStreamConnection('mq', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('task_queue', false, true, false, false);
$data = [1,2,3,4,5,6,7,8,9];
foreach ($data as $d) {
	$msg = new AMQPMessage($d, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT] );
	$channel->basic_publish($msg, '', 'task_queue');
	echo " [x] Sent ", $d, "\n";
	sleep(5);

}
$channel->close();
$connection->close();
