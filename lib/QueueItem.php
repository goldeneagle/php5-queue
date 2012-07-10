<?php

namespace Queue;

class QueueItem {
  protected $obj;

  protected $created;
  protected $started;
  protected $active;
  protected $completed;
  protected $priority;

  public function __construct($obj, $priority=QUEUE_PRIORITY_MEDIUM) {
    $this->obj       = $obj;
    $this->priority  = $priority;
    $this->created   = time();
    $this->started   = 0;
    $this->active    = false;
    $this->completed = false;
  }

  public function getObject() {
    return $this->obj;
  }

  public function getCreated() {
    return $this->created;
  }

  public function getStarted() {
    return $this->startTime;
  }

  public function activate() {
    $this->started = time();
    $this->active  = true;
  }

  public function isActive() {
    return $this->active;
  }

  public function isComplete() {
    return $this->completed;
  }

  public function setComplete($complete) {
    $this->completed = $complete;
    if ($this->completed) {
      $this->active = false;
    }
  }

  public function getPriority() {
    return $this->priority;
  }

  public function setPriority($priority) {
    $this->priority = $priority;
  }

}

?>