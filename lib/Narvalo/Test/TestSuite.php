<?php

namespace Narvalo\Test;

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

  final static function Run() {
    static::SetUp();
    static::Tests();
    static::TearDown();
  }
}

// EOF
