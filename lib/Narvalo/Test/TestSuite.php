<?php

namespace Narvalo\Test;

require_once 'Narvalo\Test\FrameworkBundle.php';

use \Narvalo\Test\Internal as _;

function run($_fun_) {
  _\startup();
  $_fun_();
  _\shutdown();
}

abstract class AbstractTestSuite {
  protected function __construct() {
    ;
  }

  abstract protected function runSuite();

  final static function Run() {
    $suite = new static();
    $suite->_run();
  }

  private final function _run() {
    _\startup();
    $this->runSuite();
    _\shutdown();
  }
}

namespace Narvalo\Test\Internal;

use \Narvalo\Test\Framework;

function startup() {
  $mod = new Framework\TestModule();
  $mod->getProducer()->startup();
}

function shutdown() {
  $mod = new Framework\TestModule();
  $mod->getProducer()->shutdown();
}

// EOF
