<?php

namespace Queue;

class JobQueue {
  /**
   * @var Queue[]
   */
  protected $queues;
  protected $jobs;

  protected $priorityOrder;
  protected $defaultPriority = 50;

  public function __construct() {
    $priorityOrder = array();
    $this->queues  = array();
  }

  /**
   * Add an existing persistent queue to our job queue.
   * Queue added if the queue-name hasn't already been used
   **/
  public function addQueue($config) {
    if (empty($this->queues[$config['name']])) {
      $this->queues[$config['name']] = new Queue($config);

      // Add to the priority Order
      $priority = $this->defaultPriority;
      if (!empty($config['priority'])) {
        $priority = $config['priority'];
      }

      if (empty($this->priorityOrder[$priority])) {
        $this->priorityOrder[$priority] = array();
      }

      $this->priorityOrder[$priority][] = $config['name'];
    } else {
      echo "WARN: queue {$config['name']} already exists\n";
    }
  }

  /**
   * Returns the number of Jobs queued and waiting
   **/
  public function size() {
    $size = 0;
    foreach($this->queues as $queue) {
      //print_r($queue);
      $size += $queue->size();
    }
    return $size;
  }

  /**
   * Returns whether there are any more jobs to process
   **/
  public function isEmpty() {
    foreach($this->queues as $queue) {
      if ($queue->hasNext()) {
        // break out on the first non-empty queue
        return false;
      }
    }
    return true;
  }

  public function getNextQueue() {
    uksort($this->queues, function ($a, $b) {
      return $a < $b;
    });
    foreach ($this->queues as $queue) {
      if ($queue->hasNext()) {
        return $queue;
      }
    }
  }

  public function getNextJob() {

  }

  public function peekNextJob() {

  }

  public function getNextAppJob($appname) {
    if (isset($this->queues[$appname])) {
      return $this->queues[$appname]->next();
    }
  }
}

?>