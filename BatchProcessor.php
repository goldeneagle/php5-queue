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

class BatchJob {
	protected $appName;
	protected $task;
	protected $data;
	protected $status;
	protected $retried=0;
	protected $created;
	protected $started;
	protected $completed;
	
	public function __construct($name=false, $task=false, $data=false) {
		if ($name) { $this->setName($name); }
		if ($task) { $this->setTask($task); }
		if ($data) { $this->setData($data); }
		$this->created = time();
		
		// TODO: Create a unique job number
		// Perhaps when it is added to a queue?
	}

	public function getName() {
		return $this->name;
	}
	public function setName($name) {
		$this->name = $name;
	}

	public function getTask() {
		return $this->task;
	}
	public function setTast($task) {
		$this->task = $task;
	}

	public function getData() {
		return $this->data;
	}
	public function setData($data) {
		$this->data = $data;
	}

	public function markAsStarted() {}
	public function markAsCompleted() {}
}

?>