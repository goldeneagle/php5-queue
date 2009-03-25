<?php


class BatchProcessor {
	protected $config;
	protected $isInitialised = false;
	
	protected $jobQueue;	
	
	public function __construct($config=false) {
		if ($config) {
			$this->setConfig($config);
		}
	}
	
	public function setConfig($config) {
		$this->config = $config;
		if (!$this->isInitialised) {
			$this->createJobQueue();
		}
		$this->init();
	}
	
	public function size() {
		return $this->jobQueue->size();
	}
	
	public function isEmpty() {
		return $this->jobQueue->isEmpty();
	}



	###
	### Protected methods
	###

	protected function init() {
		// Initialise job queues with existing persistent queues
		if (!empty($this->config['queues'])) {
			$this->initJobQueues($this->config['queues']);	
		}
	}
	
	protected function initJobQueues($queues) {
		foreach($queues as $queueConfig) {
			$this->jobQueue->addQueue($queueConfig);
		}
	}
	
	protected function createJobQueue() {
		$this->jobQueue = new JobQueue();
	}

}

class JobQueue {
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
	public function getNextJob() {}
	public function peekNextJob() {}
	
	public function getNextAppJob($appname) {}
}

?>