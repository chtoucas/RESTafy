<?php

namespace Narvalo\Test\Framework\Unit;

require_once 'Narvalo\Framework\Test.php';

class TestSuite extends TapActor {
}

interface UnitFixture {
  function setUp();
  function tearDown();
}

