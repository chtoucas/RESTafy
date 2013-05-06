<?php

require_once 'Narvalo/Test/More.php';
require_once 'Narvalo/Test/TestSuite.php';

use \Narvalo\Test;

class MyMoreTestSuite extends Test\TestSuite {
  static $T;

  static function SetUp() {
    self::$T = new Test\More();
    self::$T->plan(3);
  }

  static function Tests() {
    self::Test1();
    self::Test2();
    self::Test3();
  }

  static function Test1() {
    self::$T->assert(\TRUE, 'Passing test.');
  }

  static function Test2() {
    self::$T->pass('Passing test.');
  }

  static function Test3() {
    self::$T->fail('Failing test.');
  }
}

MyMoreTestSuite::Run();

// EOF
