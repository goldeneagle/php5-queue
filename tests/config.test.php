<?php

namespace Queue\Test;

require_once(dirname(__FILE__)."/../Queue.php");
set_time_limit(200);

define('QUEUE_TEMPORARY_DIR', dirname(__FILE__).'/tmp/');
if (file_exists(QUEUE_TEMPORARY_DIR)) {
  if (!is_dir(QUEUE_TEMPORARY_DIR)) {
    throw new \Exception(QUEUE_TEMPORARY_DIR." is not a directory\n");
  }
} else {
  mkdir(QUEUE_TEMPORARY_DIR);
}


# recursively remove a directory
function rrmdir($dir) {
  foreach(glob($dir . '/*') as $file) {
    if(is_dir($file))
      rrmdir($file);
    else
      unlink($file);
  }
  rmdir($dir);
}

# empty a directory
function cleardir($dir) {
  foreach(glob($dir . '/*') as $file) {
    if(is_dir($file))
      rrmdir($file);
    else
      unlink($file);
  }
}

function deleteTemporaryDirectory() {
  cleardir(QUEUE_TEMPORARY_DIR);
}


ini_set('xdebug.show_exception_trace', 0);

?>

