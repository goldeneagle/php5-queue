<?php

define('QUEUE_PRIORITY_HIGH', 	1);
define('QUEUE_PRIORITY_MEDIUM',	2);
define('QUEUE_PRIORITY_LOW', 		4);

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
	
	public function getItem() {
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

abstract class QueueStorage {
	protected $config;
	protected $queue;

	public function __construct($config=false) {
		if ($config !== false) {
			$this->setConfig($config);
		}
		if ($this->lock()) {
			$this->open();
			$this->unlock();
		}
	}

	
	public function __destruct() {
		if ($this->lock()) {
			$this->close();
			$this->unlock();
		}
	}
	
	
	public function setConfig($config) {
		$this->config = $config;
		$this->init();
	}
	
	abstract protected function init();
	abstract public function open();
	abstract public function close();

	protected function getNewQueue() {
		$queue = array(
			QUEUE_PRIORITY_HIGH   => array(),
			QUEUE_PRIORITY_MEDIUM => array(),
			QUEUE_PRIORITY_LOW    => array(),
		);
	}

//	public function push($obj, $priority=NULL);
//	public function pop();
	
	// Save the last batch of changes to the storage
	abstract protected function persist();
	
	// Check that we have the most up-to-date queue data
	abstract protected function refresh();
	
	// Get a lock on updates to the queue
	abstract protected function lock();
	
	// Unlock the queue for other processes
	abstract protected function unlock($force = false);

	// Check whether there is a lock present
	abstract protected function isLock();

	public function add($obj) {
		$priority = $obj->getPriority();
		//echo "Priority: $priority\n";

		if ($this->lock()) { // Lock the queue while updating
			$this->refresh(); // Make sure we have the freshest queue data

			if (empty($this->queue[$priority])) {
				//echo "Creating a new $priority queue\n";
				$this->queue[$priority] = array();
			}
			$this->queue[$priority][] = $obj;

			$this->persist(); // persist the queue to storagee
			$this->unlock(); // Unlock the queue for others
		}
	}
	
	
	public function hasNext() {
		// TODO: replace with a check whether there are
		// any items waiting to be done.
		if ($this->isQueueEmpty()) {
			//echo "Queue is empty\n";
			return false;
		} elseif ($this->hasInactive()) {
			//echo "Queue has inactive elements\n";
			return true;
		}
		//echo "Queue fall through\n";
		// TODO: see if any of the queue items are stale.
		return false;
	}

	public function next() {
		// Iterate through each queue
		// * Get the first inactive element
		// * If no inactive elements
		//   * Iterate through started elements
		//   * Check that the start time is still fresh
		//   * If the start time is stale, return that one
		//  * Move on to the next queue
	}

	protected function hasUpdates() {
		// TODO: keep track if we have non-persisted updates		
		return true; //false;
	}

	protected function isQueueEmpty() {
		//print_r($this->queue);
		return (
			empty($this->queue[QUEUE_PRIORITY_HIGH]) &&
			empty($this->queue[QUEUE_PRIORITY_MEDIUM]) &&
			empty($this->queue[QUEUE_PRIORITY_LOW])
		);
	}
	
	protected function hasInactive() {
		// TODO see if there is one inactive element
		return true;
	}
	
}


class SerialisedQueueStorage extends QueueStorage {
	protected $serFile    = '/home/user/data/queue/queue.ser';
	protected $lockFile;
	protected $lockTime   = 0;
	protected $staleLimit = 10;
	
	protected $lastUpdated;

	protected function init() {
		// At this point we have a config object.
		// TODO: use config object to set the $serFile
		
		if (!empty($this->serFile)) {
			// Create a lock file name
			$this->lockFile = $this->serFile . '.LOCK';
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
			echo "DEBUG: No queue file $this->serFile found\n";
			// TODO: Initialise a new queue here
		}
	}
	
	public function close() {
		if ($this->hasUpdates()) {
			$ser = serialize($this->queue);
			file_put_contents($this->serFile, $ser);
		}
	}
	
	
	protected function persist() {
		echo "SerialisedQueueStorage->persist()\n";
		
		// Write the queue to file
		
		// Unlock the file
	}
	
	protected function refresh() {
		//echo "SerialisedQueueStorage->refresh()\n";
		$fileTouched = filectime($this->serFile);
		
		// TODO: get a lock on the file
		if ($fileTouched > $this->lastUpdated) {
			// TODO: reload file
			echo "Reloading the queue from storage\n";
			$this->open();
		}
	}

	protected function lock() {
		// Check whether we have a lock
		// And if we do, check its freshness
		
		// When we don't have a lock
		// Create a lock file
		if (file_put_contents($this->lockFile, time()) !== false) {
			$this->lockTime = filectime($this->lockFile);
			return true;
		}
		
		return false;	
	}
	
	protected function unlock($force = false) {
		// Check that we created the lock file in the first place
		$lockTime = filectime($this->lockFile);
		if ($lockTime == $this->lockTime) {
			return unlink($this->lockFile);
		} elseif ($force) {
			echo "WARN: Forcing an unlock of the queue\n";
			return unlink($this->lockFile);
		}
		return false;
	}
	
	protected function isLock() {
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

class Queue {
	protected $name;
	protected $storage;

	
	public function __construct($config=false) {
		if ($config!==false) {
			$this->setConfig($config);
		}
	}	
	
	public function setConfig($config) {
		if (is_string($config)) {
			$this->setStorage($config);
		}	
	}	
	
	public function getStorage() {
		return $this->storage;
	}

	/**
	* Adds an object to the end of the queue
	**/
	public function add($obj, $priority=false) {
		if ($priority===false) {
			$priority = QUEUE_PRIORITY_MEDIUM;
		}
		
		$item = new QueueItem($obj, $priority);
		$this->storage->add($item);
	}
	
	/**
	* Returns the first object on the queue (at the head)
	**/
	public function next() {
		return $this->storage->next();		
	}

	public function hasNext() {
		return $this->storage->hasNext();
	}
	
	protected function setStorage($storage) {
		$storeObj = $this->initStorage($storage);
		if ($storeObj) {
			$this->storage = $storeObj;
		}
	}
	
	protected function initStorage($name) {
		if (class_exists($name)) {
			$tmpStore = new $name();
			if (is_a($tmpStore, 'QueueStorage')) {
				return $tmpStore;
			} else {
				echo "ERROR: $name does not subclass QueueStorage\n";
			}
		} else {
			echo "ERROR: No queue storage class called $name found.\n";
		}
		return NULL;		
	}
	

}

?>