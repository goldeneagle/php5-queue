<?php

require_once 'Queue.php';
require_once 'BatchProcessor.php';

// Local configuration
require_once 'config.php';

$batch = new BatchProcessor($batchConfig);





?>