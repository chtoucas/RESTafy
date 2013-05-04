<?php

namespace Narvalo\Test\Framework\Modules;

require_once 'Narvalo\Test\Framework.php';

use Narvalo\Test\Framework;
use Narvalo\Test\Framework\TestModule;

abstract class TestSpecBase {
  protected function __construct() {
    ;
  }

  abstract protected function runScenario();

  static function AutoRun() {
    $spec = new static();
    $spec->Run();
  }

  static function RunInline($_scenario_) {
    self::Begin();
    $_scenario_();
    self::End();
  }

  protected static function Begin() {
    TestModule::SharedProducer()->Startup();
  }

  protected static function End() {
    TestModule::SharedProducer()->Shutdown();
  }

  function run() {
    self::Begin();
    $this->RunScenario();
    self::End();
  }
}

// EOF
