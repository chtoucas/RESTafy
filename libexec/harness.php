<?php

require_once 'Narvalo/Test/Framework.php';

use Narvalo\Test\Framework\TestHarness;

$harness = new TestHarness();
$harness->runTests(array(
  't/Narvalo/Test/Framework/more/002-plan.t',
  't/Narvalo/Test/Framework/more/003-noplan.t',
  //'t/Narvalo/Test/Framework/more/004-skipall.t',
  //'t/Narvalo/Test/Framework/more/005-bailout.t'
));

