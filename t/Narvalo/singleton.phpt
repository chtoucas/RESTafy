<?php

require_once 'NarvaloBundle.php';
require_once 'Narvalo/TestBundle.php';

use \Narvalo;
use \Narvalo\Test as t;

// Stubs.

class SingletonStub1 {
  use Narvalo\Singleton;
}

class SingletonStub2 {
  use Narvalo\Singleton;

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

//$stub1 = SingletonStub1::UniqInstance();
//$stub11 = SingletonStub1::UniqInstance();

$stub2 = SingletonStub2::UniqInstance();
t\is(\TRUE, $stub2->initialized(), 'Initialized.');

$stub21 = SingletonStub2::UniqInstance();
t\is(1, SingletonStub2::$InitializeCount, 'Initialized only once.');

