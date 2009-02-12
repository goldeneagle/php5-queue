<?php

interface Queueable {
	public function getGuid();
}

interface QueueStorageInterface {
//	public function push($obj, $priority=NULL);
//	public function pop();
	
//	public function hasNext();
//	public function next();
	
}

class SerialisedQueueStorage implements QueueStorageInterface {

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
	* Pushes an object to the end of the queue
	**/
	public function push($obj) {
		$this->storage->push($obj);
	}
	
	/**
	* Returns the first object on the queue (at the head)
	**/
	public function next() {
		return $this->storage->next();
	}

	public function hasNext() {
		return $storage->hasNext();	
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
			if (is_a($tmpStore, 'QueueStorageInterface')) {
				return $tmpStore;
			} else {
				echo "ERROR: $name does not implement QueueStorageInterface\n";
			}
		} else {
			echo "ERROR: No queue storage class called $name found.\n";
		}
		return NULL;		
	}
	

}

?>