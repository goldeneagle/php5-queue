<?php

require_once("config.test.php");

class TestSuite {

  static function suite() {
    $suite = new PHPUnit_Framework_TestSuite('PHPQueue');
    $testFiles = array(
      'TestSerializedQueue'
      );
    $suite->addTestFiles($testFiles);
    return $suite;
  }
}

?>