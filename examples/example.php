<?php

require_once(dirname(__FILE__).'/../Queue.php');

$queue = new Queue\Queue(
	array("type" => 'Queue\SerializedQueueStorage',
        "file" => dirname(__FILE__).'/test.ser')
);

if ($queue->hasNext()) {
	echo "INFO: Queue has items\n";
} else {
	echo "Adding item 1\n";
	$queue->add('http://www.example.com');
	echo "Adding item 2\n";
	$queue->add('http://www.example.net');
	echo "Adding item 3\n";
	$queue->add('http://www.example.org');
}

while($item = $queue->next()) {
	$object = $item->getObject();
	echo "Item: $object\n";
}

?>