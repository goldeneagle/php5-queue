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
   * @throws QueueLockException
   */
  public function __construct($queue) {
    $this->queue = $queue;
    if (!$this->queue->lock()) {
      throw new QueueLockException("Could not get a lock on queue");
    }
  }

  public function __destruct() {
    $this->queue->unlock();
  }
}
?>