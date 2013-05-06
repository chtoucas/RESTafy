<?php

require_once 'Narvalo/Test/RunnerBundle.php';

use Narvalo\Test\Runner;

$harness = new Runner\TestHarness();
$harness->runTests(array(
  't/i-do-not-exist.php',
  't/Narvalo/Test/more-bailout.php',
  't/Narvalo/Test/more-complex.php',
  't/Narvalo/Test/more-noplan.php',
  't/Narvalo/Test/more-plan.php',
  't/Narvalo/Test/more-raw.php',
  't/Narvalo/Test/more-skipall.php',
  't/Narvalo/Test/more-throw.php',
  't/Narvalo/Test/simple-inline.php',
  't/Narvalo/Test/simple-autorun.php',
));

// EOF
