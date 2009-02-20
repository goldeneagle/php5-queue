<?php

require_once 'Queue.php';

$queue = new Queue('SerialisedQueueStorage');
//print_r($queue);

if ($queue->hasNext()) {
	echo "INFO: Queue has items\n";
} else {
	$queue->add('http://www.example.com');
	$queue->add('http://www.example.net');
	$queue->add('http://www.example.org');
}

while($item = $queue->next()) {
	$object = $item->getObject();
	echo "Item: $object\n";
}

?>