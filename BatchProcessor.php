<?php


class BatchProcessor {
	protected $config;
	protected $isInitialised = false;
	
	protected $queues;	
	
	public function __construct($config=false) {
		if ($config) {
			$this->setConfig($config);
		}
	}
	
	public function setConfig($config) {
		$this->config = $config;
		if (!$this->isInitialised) {
			$this->init();
		}
	}


	protected function init() {
		if (!empty($this->config['queues'])) {
			$this->initQueues($this->config['queues']);	
		}
	}
	
	protected function initQueues($queues) {
		$this->queues = array();
		foreach($queues as $queueConfig) {
			$queue = new Queue($queueConfig);
			$this->queues[$queueConfig['name']] = $queue;
		}
	}
}

?>