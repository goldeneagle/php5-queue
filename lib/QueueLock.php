<?php
/**
 *
 *
 * User: manuel
 * Date: 10/7/12
 * To change this template use File | Settings | File Templates.
 */

namespace Queue;

/**
 * Lock a queue
 */
class QueueLock {
  static $counter = 0;
  function debug_string_backtrace() {
    ob_start();
    debug_print_backtrace();
    $trace = ob_get_contents();
    ob_end_clean();

    // Remove first item from backtrace as it's this function which
    // is redundant.
    $trace = preg_replace ('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);

    // Renumber backtrace items.
    $trace = preg_replace ('/^#(\d+)/me', '\'#\' . ($1 - 1)', $trace);

    return $trace;
  }

  /**
   * @param QueueStorage $queue
   * @param null|integer $timeout timeout in seconds
   * @throws QueueLockException
   */
  public function __construct($queue, $timeout = null) {
    $this->count = ++static::$counter . " " .getmypid();
    $fp = fopen("php://stdout", "w");
    fprintf($fp, "open lock %s\n", $this->count);
    fclose($fp);
    $this->queue = $queue;
    $this->locked = false;
    $this->backtrace = $this->debug_string_backtrace();
    if (!$this->queue->waitForLock($timeout)) {
      throw new QueueLockException("Could not get a lock on queue");
    }
    $this->locked = true;
  }

  public function __destruct() {
    if ($this->locked) {
      $fp = fopen("php://stdout", "w");
      fprintf($fp, "close lock %s\n", $this->count);
      fclose($fp);
      $this->queue->unlock();
    }
  }
}
?>