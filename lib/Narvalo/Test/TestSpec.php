<?php

namespace Narvalo\Test;

require_once 'Narvalo\Test\Framework.php';

final class TestSpecHelper {
  static function Run($_scenario_) {
    self::Begin();
    $_scenario_();
    self::End();
  }

  static function Begin() {
    Framework\TestModule::GetSharedProducer()->startup();
  }

  static function End() {
    Framework\TestModule::GetSharedProducer()->shutdown();
  }
}

abstract class AbstractTestSpec {
  protected function __construct() {
    ;
  }

  abstract protected function runScenario();

  static function AutoRun() {
    $spec = new static();
    $spec->run();
  }

  function run() {
    TestSpecHelper::Begin();
    $this->runScenario();
    TestSpecHelper::End();
  }
}

// EOF
