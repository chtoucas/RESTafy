<?php

require_once 'Narvalo/Test/Framework/Tap.php';

use \Narvalo\Test\Framework\Tap\TapRunner;

$runner = TapRunner::UniqInstance();

//$runner->runTest('t/Narvalo/Test/Framework/simple-autorun.php');
//$runner->runTest('t/Narvalo/Test/Framework/simple-inline.php');
//$runner->runTest('t/Narvalo/Test/Framework/more-autorun.php');
//$runner->runTest('t/Narvalo/Test/Framework/more-bailout.php');
$runner->runTest('t/Narvalo/Test/Framework/more-noplan.php');
//$runner->runTest('t/Narvalo/Test/Framework/more-plan.php');
//$runner->runTest('t/Narvalo/Test/Framework/more-skipall.php');
//$runner->runTest('t/Narvalo/Test/Framework/more-complex.php');

