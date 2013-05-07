<?php

require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Tap;

$runner = new Tap\TapRunner();

//$runner->runTest('t/arvalo/Test/more-bailout.php');

//$runner->runTest('t/Narvalo/Test/simple-inline.php');
//$runner->runTest('t/Narvalo/Test/more-noplan.php');
//$runner->runTest('t/Narvalo/Test/more-plan.php');
//$runner->runTest('t/Narvalo/Test/more-skipall.php');

//$runner->runTest('t/Narvalo/Test/more-bailout.php');
//$runner->runTest('t/Narvalo/Test/more-complex.php');
$runner->runTest('t/Narvalo/Test/more-throw.php');

//$runner->runTest('t/Narvalo/Test/more-autorun.php');
//$runner->runTest('t/Narvalo/Test/simple-autorun.php');

// EOF
