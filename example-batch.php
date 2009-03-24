<?php

// TODO: remove this when BatchProcessor supports a PriorityQueue
require_once 'Queue.php';

// Batch processor classes
require_once 'BatchProcessor.php';
require_once 'BatchJob.php';

// Local configuration
require_once 'config.php';

$batch = new BatchProcessor($batchConfig);





?>