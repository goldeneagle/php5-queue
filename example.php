<?php

require_once 'Queue.php';

$queue = new Queue('SerialisedQueueStorage');
print_r($queue);

if ($queue->hasNext()) {
	echo "INFO: Queue has items\n";
}

$queue->add('http://www.example.com');


?>