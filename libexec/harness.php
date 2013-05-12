<?php

require_once 'Narvalo/Test/RunnerBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Runner;
use \Narvalo\Test\Tap;

$harness = new Runner\TestHarness(new Tap\TapHarnessOutStream('php://stdout'));
$harness->executeTestFiles(array(
  't/i-do-not-exist.php',
  't/Narvalo/Test/more-bailout.php',
  't/Narvalo/Test/more-complex.php',
  't/Narvalo/Test/more-noplan.php',
  't/Narvalo/Test/more-plan.php',
  't/Narvalo/Test/more-raw.php',
  't/Narvalo/Test/more-skipall.php',
  't/Narvalo/Test/more-throw.php',
  't/Narvalo/Test/simple-inline.php',
));

// EOF
