<?php

namespace Queue\Test;

require_once('config.test.php');

class TestSerializedQueue extends \PHPUnit_Framework_TestCase {
  /**
   * @var Queue\Queue $queue
   */
  public $queue = null;

  function setUp() {
    deleteTemporaryDirectory();
    $this->queue = new Queue\Queue(array("type" => "Queue\SerializedQueueStorage",
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
    $this->assertInstanceOf("Queue\SerializedQueueStorage", $storage);

    $this->assertEquals(0, $this->queue->size());
    $this->assertFalse($this->queue->hasNext());

    $this->assertEquals(0, $storage->size());
    $this->assertFalse($storage->hasNext());
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
    $this->assertInstanceOf("Queue\QueueItem", $item);
    $str = $item->getObject();
    $this->assertEquals("foo", $str);
    $this->assertEquals(0, $this->queue->size());
    $this->assertFalse($this->queue->hasNext());
  }

  /**
   * @expectedException Queue\QueueEmptyException
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
      $lock = new Queue\QueueLock($storage);
      $test->assertTrue($storage->isLock());
      throw new \Exception("test");
    };

    try {
      $a();
    } catch (\Exception $e) {
    }

    $this->assertFalse($storage->isLock());
  }
}

?>