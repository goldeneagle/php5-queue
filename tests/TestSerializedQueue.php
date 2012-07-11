<?php

namespace Queue\Test;

require_once('config.test.php');

class TestSerializedQueue extends \PHPUnit_Framework_TestCase {
  /**
   * @var \Queue\Queue $queue
   */
  public $queue = null;

  function setUp() {
    deleteTemporaryDirectory();
  }

  function tearDown() {
    deleteTemporaryDirectory();
  }

  function testCreation() {
    $this->queue = new \Queue\Queue(array("type" => "Queue\SerializedQueueStorage",
                                          "file" => QUEUE_TEMPORARY_DIR."test.ser"));

    $this->assertNotNull($this->queue);
    $this->assertInstanceOf("Queue\Queue", $this->queue);
    /**
     * var Queue\SerializedQueueStorage
     */
    $storage = $this->queue->getStorage();
    $this->assertNotNull($storage);
    $this->assertInstanceOf("\Queue\SerializedQueueStorage", $storage);

    $this->assertEquals(0, $this->queue->size());
    $this->assertFalse($this->queue->hasNext());

    $this->assertEquals(0, $storage->size());
    $this->assertFalse($storage->hasNext());
  }

  function testLock() {
    $this->queue = new \Queue\Queue(array("type" => "Queue\SerializedQueueStorage",
                                          "file" => QUEUE_TEMPORARY_DIR."test.ser"));

    $storage = $this->queue->getStorage();
    $lock = $storage->getLock();
    $res = $storage->waitForLock(0);
    $this->assertFalse($res);
  }

  function testLockRelease() {
    $this->queue = new \Queue\Queue(array("type" => "Queue\SerializedQueueStorage",
                                          "file" => QUEUE_TEMPORARY_DIR."test.ser"));
    $storage = $this->queue->getStorage();
    $lock = $storage->getLock();
    unset($lock);
    $res = $storage->waitForLock(0);
    $this->assertTrue($res);
  }

  /**
   * @expectedException \Queue\QueueLockException
   */
  function testLockFail() {
    $this->queue = new \Queue\Queue(array("type" => "Queue\SerializedQueueStorage",
                                          "file" => QUEUE_TEMPORARY_DIR."test.ser"));
    $storage = $this->queue->getStorage();
    $lock = $storage->getLock();
    $lock2 = $storage->getLock(0);
  }

  function testAddString() {
    $this->queue = new \Queue\Queue(array("type" => "Queue\SerializedQueueStorage",
                                          "file" => QUEUE_TEMPORARY_DIR."test.ser"));
    $this->queue->add("foo");
    $this->assertEquals(1, $this->queue->size());
    $this->assertTrue($this->queue->hasNext());
  }

  function testGetString() {
    $this->queue = new \Queue\Queue(array("type" => "Queue\SerializedQueueStorage",
                                          "file" => QUEUE_TEMPORARY_DIR."test.ser"));
    $this->queue->add("foo");
    $item = $this->queue->next();
    $this->assertNotNull($item);
    $this->assertInstanceOf("\Queue\QueueItem", $item);
    $str = $item->getObject();
    $this->assertEquals("foo", $str);
    $this->assertEquals(0, $this->queue->size());
    $this->assertFalse($this->queue->hasNext());
  }

  /**
   * @expectedException \Queue\QueueEmptyException
   */
  function testGetFromEmptyQueue() {
    $this->queue = new \Queue\Queue(array("type" => "Queue\SerializedQueueStorage",
                                          "file" => QUEUE_TEMPORARY_DIR."test.ser"));
    $res = $this->queue->next();
  }

  function testQueueLock() {
    $this->queue = new \Queue\Queue(array("type" => "Queue\SerializedQueueStorage",
                                          "file" => QUEUE_TEMPORARY_DIR."test.ser"));
    $storage = $this->queue->getStorage();
    $this->assertFalse($storage->isLock());
    $lock = new Queue\QueueLock($this->queue->getStorage());
    $this->assertTrue($storage->isLock());
  }

  function testQueueLockException() {
    $this->queue = new \Queue\Queue(array("type" => "Queue\SerializedQueueStorage",
                                          "file" => QUEUE_TEMPORARY_DIR."test.ser"));
    $storage = $this->queue->getStorage();
    $test = $this;
    $a = function () use ($storage, $test) {
      $lock = new \Queue\QueueLock($storage);
      $test->assertTrue($storage->isLock());
      throw new \Exception("test");
    };

    try {
      $a();
    } catch (\Exception $e) {
    }

    $this->assertFalse($storage->isLock());
  }

  function testMultiProcess() {
    $this->queue = new \Queue\Queue(array("type" => "Queue\SerializedQueueStorage",
                                          "file" => QUEUE_TEMPORARY_DIR."test.ser"));
    $pm = new \Spork\ProcessManager();
    $fp = fopen("php://stdout", "w");
    $storage = $this->queue->getStorage();
    $queue = $this->queue;
    $test = $this;

    $pm->fork(function () use ($fp, $queue, $storage, $test) {
      fprintf($fp, "before sleep\n");
      $test->assertEquals(0, $queue->size());
      sleep(2);
      $test->assertEquals(1, $queue->size());
      $queue->add("foobar");
    })->then(function (\Spork\Fork $fork) use ($fp, $queue, $storage, $test) {
      $test->assertEquals(2, $queue->size());
    });
    sleep(1);
    $queue->add("foobar");
    fclose($fp);
  }

  function testMultiProcessLock() {
    $pm = new \Spork\ProcessManager();
    $fp = fopen("php://stdout", "w");
    $test = $this;
    fprintf($fp, "foobar\n");

    $pm->fork(function () use ($fp, $test) {
      $queue = new \Queue\Queue(array("type" => "Queue\SerializedQueueStorage",
                                      "file" => QUEUE_TEMPORARY_DIR."test.ser"));
      $storage = $queue->getStorage();
      sleep(1);
      fprintf($fp, "before second add\n");
      $queue->add("foobar");
      fprintf($fp, "after second add\n");
      $queue->add("foobar");
      fprintf($fp, "after third add\n");
      sleep(2);
      fprintf($fp, "all closed\n");
    })->then(function (\Spork\Fork $fork) use ($fp, $test) {
      $queue = new \Queue\Queue(array("type" => "Queue\SerializedQueueStorage",
                                      "file" => QUEUE_TEMPORARY_DIR."test.ser"));
      $storage = $queue->getStorage();
      fprintf($fp, "finally\n");
      fprintf($fp, "Queue size %s\n", $queue->size());
      $test->assertEquals(2, $queue->size());
      fclose($fp);
    });

    $foo = function () use ($fp) {
      $queue = new \Queue\Queue(array("type" => "Queue\SerializedQueueStorage",
                                            "file" => QUEUE_TEMPORARY_DIR."test.ser"));
      $storage = $queue->getStorage();
      $lock = new \Queue\QueueLock($storage);
      fprintf($fp, "adding\n");
      $queue->add("foobar", null, $lock);
      fprintf($fp, "after first add\n");
      sleep(2);
      fprintf($fp, "before unset\n");
      unset($lock);
      fprintf($fp, "after unset\n");
    };
    $foo();
    fprintf($fp, "last one\n");
  }
}

?>