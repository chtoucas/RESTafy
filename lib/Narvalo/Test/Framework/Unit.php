<?php

namespace Narvalo\Test\Framework\Unit;

require_once 'Narvalo\Test\Framework.php';

//class TestSuite extends TapActor {
//}

interface UnitFixture {
  function setUp();
  function tearDown();
}

