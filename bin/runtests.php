<?php

require_once 'Narvalo/Test/Framework.php';

use Narvalo\Test\Framework\TestHarness;

$harness = new TestHarness();
$harness->runTests(array(
  't/more/002-plan.t',
  't/more/003-noplan.t',
  't/more/004-skipall.t',
  't/more/005-bailout.t'
));

