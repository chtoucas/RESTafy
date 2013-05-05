<?php

namespace Narvalo\Test;

require_once 'Narvalo\Test\FrameworkBundle.php';

class TestSuite {
  static function SetUp() {
    ;
  }

  static function Tests() {
    ;
  }

  static function TearDown() {
    ;
  }

  final static function AutoRun() {
    $mod = new Framework\TestModule();
    $producer = $mod->getProducer();

    $producer->startup();

    self::Run();

    $producer->shutdown();
  }

  final static function Run() {
    static::SetUp();
    static::Tests();
    static::TearDown();
  }
}

// EOF
