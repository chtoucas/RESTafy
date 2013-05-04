<?php

namespace Narvalo\Test\Unit;

require_once 'Narvalo\Test.php';

class TestSuite extends TapActor {
}

interface UnitFixture {
  function setUp();
  function tearDown();
}

