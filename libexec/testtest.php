<?php

require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/RunnerBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Framework;
use \Narvalo\Test\Runner;
use \Narvalo\Test\Tap;

$producer = new Tap\DefaultTapProducer();
Framework\TestModulesKernel::Bootstrap($producer, \TRUE);
$runner = new Runner\TestRunner($producer);

$test_suite = new Runner\FileTestSuite('t/Narval/Test/simple-inline.php');

$runner->runTestSuite($test_suite);

exit();

$runner->runTestFile('t/arvalo/Test/more-bailout.php');

$runner->runTestFile('t/Narvalo/Test/simple-inline.php');
//$runner->runTestFile('t/Narvalo/Test/more-noplan.php');
//$runner->runTestFile('t/Narvalo/Test/more-plan.php');
//$runner->runTestFile('t/Narvalo/Test/more-skipall.php');

//$runner->runTestFile('t/Narvalo/Test/more-bailout.php');
$runner->runTestFile('t/Narvalo/Test/more-complex.php');
//$runner->runTestFile('t/Narvalo/Test/more-throw.php');

//$runner->runTestFile('t/Narvalo/Test/more-autorun.php');

// EOF
