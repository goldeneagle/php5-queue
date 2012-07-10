<?php

namespace Queue;

define('BATCH_JOB_CREATED',    0);
define('BATCH_JOB_QUEUED',     1);
define('BATCH_JOB_ACTIVE',     2);
define('BATCH_JOB_HALTED',     4);
define('BATCH_JOB_COMPLETED',  8);
define('BATCH_JOB_FAILED',     16);
define('BATCH_JOB_INCOMPLETE', 32);
define('BATCH_JOB_CANCELLED',  128);


class BatchJob {
	protected $appName;
	protected $task;
	protected $data    = NULL;
	protected $status  = BATCH_JOB_CREATED;
	protected $retried = 0;
	
	// Timestamps
	protected $created;
	protected $started;
	protected $completed;
	
	// Store a list of next tasks for this job
	protected $nexttasks;
	
	public function __construct($name=false, $task=false, $data=false) {
		if ($name) { $this->setName($name); }
		if ($task) { $this->setTask($task); }
		if ($data) { $this->setData($data); }
		
		// TODO: Create a unique job number
		// Perhaps when it is added to a queue?
		
		$this->status  = BATCH_JOB_CREATED;
		$this->created = time();
	}

	public function getName()      { return $this->name;  }
	public function setName($name) { $this->name = $name; }
	public function getTask()      { return $this->task;  }
	public function setTast($task) { $this->task = $task; }
	public function getData()      { return $this->data;  }
	public function setData($data) { $this->data = $data; }


	public function setAsStarted() {
		if ($this->status == BATCH_JOB_QUEUED ||
			$this->status == BATCH_JOB_FAILED) {
			$this->status  = BATCH_JOB_ACTIVE;
			$this->started = time();
		}
	}
	
	public function setAsCompleted() {
		$this->status    = BATCH_JOB_COMPLETED;
		$this->completed = time();
	}
	
	public function setAsFailed() {
		$this->status = BATCH_JOB_FAILED;
		$this->retried++;
	}
}


?>