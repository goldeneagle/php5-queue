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
    $this->queue = new \Queue\Queue(array("type" => "Queue\SerializedQueueStorage",
                                   "file" => QUEUE_TEMPORARY_DIR."test.ser"));
  }

  function tearDown() {
    deleteTemporaryDirectory();
  }

  function testCreation() {
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
    $storage = $this->queue->getStorage();
    $lock = $storage->getLock();
    $res = $storage->waitForLock(0);
    $this->assertFalse($res);
  }

  function testLockRelease() {
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
    $storage = $this->queue->getStorage();
    $lock = $storage->getLock();
    $lock2 = $storage->getLock(0);
  }

  function testAddString() {
    $this->queue->add("foo");
    $this->assertEquals(1, $this->queue->size());
    $this->assertTrue($this->queue->hasNext());
  }

  function testGetString() {
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
    $res = $this->queue->next();
  }

  function testQueueLock() {
    $storage = $this->queue->getStorage();
    $this->assertFalse($storage->isLock());
    $lock = new Queue\QueueLock($this->queue->getStorage());
    $this->assertTrue($storage->isLock());
  }

  function testQueueLockException() {
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
    $pm = new \Spork\ProcessManager();
    $fp = fopen("php://stdout", "w");
    $storage = $this->queue->getStorage();
    $queue = $this->queue;
    $test = $this;

    $pm->fork(function () use ($fp, $queue, $storage, $test) {
      fprintf($fp, "before sleep\n");
      sleep(2);
      fprintf($fp, "after sleep\n");
      echo posix_getpid();
    })->then(function (\Spork\Fork $fork) {
      printf("parent %d forked child %s!", posix_getpid(), $fork->getOutput());
    });
    fprintf($fp, "after fork\n");
    echo "foobar\n";
    fclose($fp);
  }
}

?>