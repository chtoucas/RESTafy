<?php

namespace Narvalo\Test\Suites;

require_once 'NarvaloBundle.php';

use \Narvalo;

// Core classes.
// #################################################################################################

// {{{ TestSuite

interface TestSuite {
  function getName();

  function setup();
  function execute();
  function teardown();
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ AbstractTestSuite

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

// }}} ---------------------------------------------------------------------------------------------

// Custom test suites.
// #################################################################################################

// {{{ FileTestSuite

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

// }}} ---------------------------------------------------------------------------------------------

// EOF
