<?php

require_once 'NarvaloBundle.php';
require_once 'Narvalo\TestBundle.php';

use Narvalo\Test as t;
use Narvalo\Singleton;

t\no_plan();

// Stubs.

class Stub1 {
  use Singleton;
}

class Stub2 {
  use Singleton;

  static $InitializeCount = 0;

  private $_initialized = \FALSE;

  private function _initialize() {
    self::$InitializeCount++;
    $this->_initialized = \TRUE;
  }

  function initialized() {
    return $this->_initialized;
  }
}

// AAA.

//$stub1 = Stub1::UniqInstance();
//$stub11 = Stub1::UniqInstance();

$stub2 = Stub2::UniqInstance();
t\is(\TRUE, $stub2->initialized(), 'Initialized.');

$stub21 = Stub2::UniqInstance();
t\is(1, Stub2::$InitializeCount, 'Initialized only once.');

