<?php

namespace Queue;

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


?>