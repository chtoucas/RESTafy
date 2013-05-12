<?php

namespace Narvalo\Test;

require_once 'NarvaloBundle.php';

use \Narvalo;

interface TestSuite {
  function getName();
  function setup();
  function execute();
  function teardown();
}

abstract class AbstractTestSuite implements TestSuite {
  protected function __construct() {
    ;
  }

  abstract function execute();

  abstract function getName();

  function setup() {
    ;
  }

  function teardown() {
    ;
  }
}

class FileTestSuite extends AbstractTestSuite {
  private $_file;

  function __construct($_file_) {
    $this->_file = $_file_;
  }

  function getName() {
    return $this->_file;
  }

  function execute() {
    Narvalo\DynaLoader::LoadFile($this->_file);
  }
}

// EOF
