<?php

define('QUEUE_PRIORITY_HIGH', 	0);
define('QUEUE_PRIORITY_MEDIUM',	1);
define('QUEUE_PRIORITY_LOW', 		2);

class QueueItem {
	protected $obj;
	protected $completed = false;
	
	function getItem() {
		return $this->obj;
	}
	
	function setItem($item) {
		$this->item = $item;
	}
	
	function isComplete() {
		return $this->completed;
	}
	
	function setComplete($complete) {
		$this->completed = $complete;
	}
}

interface Queueable {
	public function getGuid();
}

abstract class QueueStorage {
	protected $config;
	protected $queue;

	public function __construct($config=false) {
		if ($config !== false) {
			$this->setConfig($config);
		}
		$this->open();
	}
	
	public function __destruct() {
		$this->close();
	}
	
	public function setConfig($config) {
		$this->config = $config;
	}
	
	abstract public function open();
	abstract public function close();

//	public function push($obj, $priority=NULL);
//	public function pop();
	
//	public function hasNext();
//	public function next();

	abstract protected function persist();


	public function add($obj, $priority) {
		if (!empty($this->queue[$priority])) {
			$this->queue[$priority] = array();
		}
		$this->queue[$priority][] = $obj;
		$this->persist();
	}

	public function hasUpdates() {
		// TODO: keep track of whether the queues have been updated	
		return true;
	}

	public function hasNext() {
		return !$this->isQueueEmpty();
	}

	protected function isQueueEmpty() {
		return empty($this->queue) || (
			empty($this->queue[QUEUE_PRIORITY_HIGH]) &&
			empty($this->queue[QUEUE_PRIORITY_MEDIUM]) &&
			empty($this->queue[QUEUE_PRIORITY_LOW])
		);
	}


	
}

class SerialisedQueueStorage extends QueueStorage {
	protected $serFile = '/home/user/data/queue/queue.ser';

	public function open() {
		if (file_exists($this->serFile)) {
			$ser = file_get_contents($this->serFile);
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
		echo "SerialisedQueueStorate->persist()\n";
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
		$this->storage->add($obj, $priority);
	}
	
	/**
	* Returns the first object on the queue (at the head)
	**/
	public function next() {
		
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