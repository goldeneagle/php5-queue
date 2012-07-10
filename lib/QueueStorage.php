<?php

namespace Queue;

abstract class QueueStorage {
  protected $config;
  protected $queue;

  public function __construct($config=false) {
    if ($config !== false) {
      $this->setConfig($config);
    }
  }

  public function __destruct() {
    if ($this->waitForLock()) {
      $this->close();
      $this->unlock();
    }
  }


  public function setConfig($config) {
    $this->config = $config;

    $this->init();

    if ($this->waitForLock()) {
      $this->open();
      $this->unlock();
    }
  }

  abstract public function init();
  abstract public function open();
  abstract public function close();
  abstract public function size();

  protected function getNewQueue() {
    $queue = array();
  }

//	public function push($obj, $priority=NULL);
//	public function pop();

  /**
   * Save the last batch of changes to the storage
   */
  abstract protected function persist();

  /**
   * Check that we have the most up-to-date queue data
   */
  abstract protected function refresh();

  /**
   * Get a lock on updates to the queue
   */
  abstract public function lock();

  /**
   * Unlock the queue for other processes
   */
  abstract public function unlock($force = false);

  /**
   * Wait for around until we get a lock
   */
  abstract public function waitForLock($timeout = false);

  /**
   * Check whether there is a lock present
   */
  abstract public function isLock();

  public function add($obj) {
    $lock = new QueueLock($this);
    $this->refresh(); // Make sure we have the freshest queue data

    $this->queue[] = $obj;

    $this->persist(); // persist the queue to storagee
  }


  public function hasNext() {
    $this->refresh();
    return !$this->isQueueEmpty();
  }

  public function next() {
    $item = NULL;
    $lock = new QueueLock($this);
    $this->refresh();

    if (count($this->queue) == 0) {
      throw new QueueEmptyException("queue is empty");
    }
    $item = array_shift($this->queue);

    $this->persist();
    return $item;
  }

  protected function hasUpdates() {
    // TODO: keep track if we have non-persisted updates
    //return true; //false;
  }

  protected function isQueueEmpty() {
    return empty($this->queue);
  }
}

?>