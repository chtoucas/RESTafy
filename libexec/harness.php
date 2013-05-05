<?php

require_once 'Narvalo/Test/TestHarness.php';

use Narvalo\Test\TestHarness;

$harness = new TestHarness();
$harness->runTests(array(
  't/Narvalo/Test/more-plan.php',
  't/Narvalo/Test/more-noplan.php',
  't/Narvalo/Test/more-skipall.php',
  't/Narvalo/Test/more-bailout.php'
));

