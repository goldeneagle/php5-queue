<?php

require_once 'Queue.php';

$queue = new Queue('SerialisedQueueStorage');
print_r($queue);

?>