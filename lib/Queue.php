<?php

namespace Queue;

define('QUEUE_PRIORITY_HIGH', 	1);
define('QUEUE_PRIORITY_MEDIUM',	2);
define('QUEUE_PRIORITY_LOW', 		4);




class Queue {
  /**
   * @var string
   */
  protected $name;
  /**
   * @var QueueStorage
   */
  protected $storage;
  /**
   * @var array
   */
  protected $config;

/****	
	public function __construct($storage, $config=false) {
		$this->setStorage($storage);
		if ($config!==false) {
			$this->setConfig($config);
		}
	}
****/

	public function __construct($config) {
		$this->setStorage($config['type']);
		$this->setConfig($config);
	}

	
	public function setConfig($config) {
		$this->config = $config;
		$this->storage->setConfig($config);
	}	
	
	public function getStorage() {
		return $this->storage;
	}

  /**
   * Adds an object to the end of the queue
   * @param null|QueueLock $lock
   * @param mixed $obj
   * @param int|boolean $priority
   **/
  public function add($obj, $priority=false, $lock = null) {
		if ($priority===false) {
			$priority = QUEUE_PRIORITY_MEDIUM;
		}
		
		$item = new QueueItem($obj, $priority);
		$this->storage->add($item, $lock);
	}
	
	/**
   * @param null|QueueLock $lock
   * @return QueueItem first object on the queue (at the head)
   **/
	public function next($lock = null) {
		return $this->storage->next($lock);
	}

  /**
   * @param null|QueueLock $lock
   * @return bool
   */
  public function hasNext($lock = null) {
		return $this->storage->hasNext($lock);
	}

  /**
   * @param null|QueueLock $lock
   * @return integer
   */
  public function size($lock = null) {
		return $this->storage->getSize($lock);
	}
	
	protected function setStorage($storage) {
		$storeObj = $this->initStorage($storage);
		if ($storeObj) {
			$this->storage = $storeObj;
		}
	}
	
	protected function initStorage($name, $config=false) {
		if (class_exists($name)) {
			$tmpStore = new $name($config);
			if (is_a($tmpStore, 'Queue\QueueStorage')) {
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