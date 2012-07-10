<?php

// TODO: remove this when BatchProcessor supports a PriorityQueue
require_once(dirname(__FILE__).'/../Queue.php');

$batchConfig = array(
  "type" => "Queue\SerializedQueueStorage",
  "file" => dirname(__FILE__)."/job-test.ser"
);
$batch = new Queue\BatchProcessor($batchConfig);
$size = $batch->size();

echo "Batch has $size jobs\n";
echo "Batch is ", ($batch->isEmpty()?'':'not '), "empty\n";

?>