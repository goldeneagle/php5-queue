<?php

namespace Queue;

abstract class QueueStorage {
  protected $config;
  protected $queue;
  /**
   * @var null|integer Timeout in seconds to use when acquiring a QueueLock
   */
  protected $timeout;
  /**
   * @var null|QueueLock QueueLock owned by parent, this lock is used instead of acquiring a new lock for operations
   */
  protected $lock;

  public function __construct($config = false) {
    if ($config !== false) {
      $this->setConfig($config);
    }
  }

  public function __destruct() {
    /* can't create a lock here because we are shutting down */
    if ($this->lock === null) {
      $this->waitForLock($this->timeout);
    }
    $this->close();
    if ($this->lock === null) {
      $this->unlock();
    }
  }

  public function setConfig($config) {
    $this->config = $config;
    if (isset($config['timeout'])) {
      $this->timeout = $config['timeout'];
    } else {
      $this->timeout = null;
    }

    if (isset($config['lock'])) {
      $this->lock = $config['lock'];
    } else {
      $this->lock = null;
    }

    $this->init();

    $lock = new QueueLock($this, $this->timeout);
    $this->open();
  }

  abstract protected function init();
  abstract protected function open();
  abstract protected function close();
  abstract protected function size();

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
  abstract public function waitForLock($timeout = null);

  /**
   * Check whether there is a lock present
   */
  abstract public function isLock();

  /**
   * @param null|integer $timeout
   * @param null|QueueLock $lock
   * @return QueueLock
   */
  public function getLock($timeout = null, $lock = null) {
    if ($timeout === null) {
      $timeout = $this->timeout;
    }
    if ($lock === null) {
      if ($this->lock === null) {
        $lock = new QueueLock($this, $timeout);
      } else {
        $lock = $this->lock;
      }
    }
    return $lock;
  }

  public function getSize($lock = null) {
    $lock = $this->getLock(null, $lock);
    $this->refresh();
    return $this->size();
  }

  /**
   * @param mixed $obj
   * @param null|QueueLock $lock optional lock, if a lock for the queue has already been acquired
   */
  public function add($obj, $lock = null) {
    $lock = $this->getLock(null, $lock);
    $this->refresh(); // Make sure we have the freshest queue data

    $this->queue[] = $obj;

    $this->persist(); // persist the queue to storagee
  }

  /**
   * @param null|QueueLock $lock optional lock, if a lock for the queue has already been acquired
   * @return bool
   */
  public function hasNext($lock = null) {
    $lock = $this->getLock(null, $lock);

    $this->refresh();
    return !$this->isQueueEmpty();
  }

  /**
   * @return QueueItem|null
   * @param null|QueueLock $lock optional lock, if a lock for the queue has already been acquired
   * @throws QueueEmptyException
   */
  public function next($lock = null) {
    $item = NULL;
    $lock = $this->getLock(null, $lock);

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

  /**
   * @param null|QueueLock $lock optional lock, if a lock for the queue has already been acquired
   * @return bool
   */
  protected function isQueueEmpty($lock = null) {
    $this->refresh();
    return empty($this->queue);
  }
}

?>