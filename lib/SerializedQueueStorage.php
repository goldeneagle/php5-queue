<?php

namespace Queue;

class SerializedQueueStorage extends QueueStorage {
  protected $serFile    = '/home/user/data/queue/queue.ser';
  protected $lockFile;
  protected $lockTime   = 0;
  protected $staleLimit = 10;

  protected $lastUpdated;

  public function init() {
    //echo "SerialisedQueueStorage->init()\n";

    // At this point we have a config object.
    if ($this->config) {
      if (is_string($this->config)) {
        $this->serFile = $this->config;
      } elseif (is_array($this->config)) {
        if (isset($this->config['staleLimit'])) {
          $this->staleLimit = $this->config['staleLimit'];
        }

        if (!empty($this->config['file'])) {
          $this->serFile = $this->config['file'];
        } else {
          $this->serFile = $this->config['datapath'] .
                           $this->config['name'] . '.ser';
        }

      } elseif (is_object($this->config)) {
        $this->serFile = $this->config->file;
      }
    }

    if (!empty($this->serFile)) {
      echo "INFO: Queue file: {$this->serFile}\n";
      // Create a lock file name
      $this->lockFile = $this->serFile . '.LOCK';
      //echo "INFO: Lock file: {$this->lockFile}\n";
    }
  }

  public function open() {
    if (file_exists($this->serFile)) {
      $ser = file_get_contents($this->serFile);
      $this->lastUpdated = filectime($this->serFile);
      $obj = unserialize($ser);

      // TODO: Do some tighter checking here
      if (!empty($obj)) {
        $this->queue = $obj;
      }
    } else {
      //echo "DEBUG: No queue file $this->serFile found\n";
      // TODO: Initialise a new queue here
    }
  }

  public function close() {
    if ($this->hasUpdates()) {
      $ser = serialize($this->queue);
      file_put_contents($this->serFile, $ser);
    }
  }

  public function size() {
    return count($this->queue);
  }


  protected function persist() {
    //echo "SerialisedQueueStorage->persist()\n";
    $ser = serialize($this->queue);
    file_put_contents($this->serFile, $ser);
    $this->lastUpdated = time(); //filectime($this->serFile);
    //echo "LastUpdated: {$this->lastUpdated} (", time(), ")\n";
  }


  protected function refresh() {
    if (file_exists($this->serFile)) {
      //echo "SerialisedQueueStorage->refresh()\n";
      $fileTouched = filectime($this->serFile);

      // TODO: get a lock on the file
      if ($fileTouched > $this->lastUpdated) {
        // TODO: reload file
        echo "Reloading the queue from storage {$fileTouched}:{$this->lastUpdated}\n";
        $this->open();
      }
    }
  }

  public function lock() {
    // Check whether we have a lock
    $lockExists = file_exists($this->lockFile);

    if ($lockExists) {
      // And if we do, check its freshness
      $lockTime = filectime($this->lockFile);
      $duration = time() - $lockTime;

      if ($duration > $this->staleLimit) {
        // Force an unlock if the lock is stale
        echo "WARN: Forcing an unlock of a stale lock\n";
        $isUnlocked = $this->unlock(true);

        if ($isUnlocked) {
          $lockExists = false;
        } else {
          echo "ERROR: Can't force an unlock of stale lock\n";
        }
      }
    }

    // When we don't have a lock
    if (!$lockExists) {
      // Create a lock file
      if (file_put_contents($this->lockFile, time()) !== false) {
        $this->lockTime = filectime($this->lockFile);
        return true;
      } else {
        echo "WARN: Creating lock failed: {$this->lockFile}\n";
      }
    }
    return false;
  }

  public function unlock($force = false) {
    // Check that we created the lock file in the first place
    $lockTime = filectime($this->lockFile);
    if ($lockTime == $this->lockTime) {
      return unlink($this->lockFile);
    } elseif ($force) {
      echo "WARN: Forcing an unlock of the queue\n";
      $isUnlocked = unlink($this->lockFile);
      if ($isUnlocked) {
        $this->lockTime = 0;
      }
      return $isUnlocked;
    }
    return false;
  }

  /**
   * Wait around then get a lock on the file
   * @param integer|null $timeout timeout in seconds
   **/
  public function waitForLock($timeout = null) {
    if ($timeout === null) {
      $timeout = $this->staleLimit;
    }

    $startTime   = time();
    $haveLock    = false;
    $haveTimeout = false;

    while(!$haveLock && !$haveTimeout) {
      $isLocked = file_exists($this->lockFile);
      if ($isLocked) {
        $lockTime = filectime($this->lockFile);
        $duration = time() - $lockTime;
        if ($duration > $this->staleLimit) {
          // Force an unlock if the lock is stale
          echo "WARN: Forcing an unlock after a wait timeout\n";
          $isLocked = !$this->unlock(true);
          if ($isLocked) {
            echo "ERROR: Couldn't unlock the queue\n";
            //break;
          }
        }
      }

      if ($isLocked) {
        // See if we haven't waited long enough yet
        $waited = time() - $startTime;
        if ($waited > $timeout) {
          echo "WARN: Waited for $timeout and still haven't got a lock\n";
          $haveTimeout = true;
        }
      } else {
        // Updating haveLock
        $haveLock = $this->lock();
        if (!$haveLock) {
          echo "INFO: We didn't get a lock\n";
          // wait before retrying
          sleep(1); echo '.';
        }
      }
    }

    return $haveLock;
  }

  public function isLock() {
    if (file_exists($this->lockFile)) {
      // TODO: keep track of whether we've done this before
      // Rather than rechecking the filectime

      // Check whether the lock is fresh
      $staleTime = time() - filectime($this->lockFile);
      if ($staleTime > $this->staleLimit) {
        echo "WARN: File lock is stale. Forcing an unlocking\n";
        return !$this->unlock(true);
      }
      return true;
    }
    return false;
  }


}

?>