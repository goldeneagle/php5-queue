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
  /**
   * @param QueueStorage $queue
   * @param null|integer $timeout timeout in seconds
   * @throws QueueLockException
   */
  public function __construct($queue, $timeout = null) {
    $this->queue = $queue;
    $this->locked = false;
    if (!$this->queue->waitForLock($timeout)) {
      throw new QueueLockException("Could not get a lock on queue");
    }
    $this->locked = true;
  }

  public function __destruct() {
    if ($this->locked) {
      $this->queue->unlock();
    }
  }
}
?>