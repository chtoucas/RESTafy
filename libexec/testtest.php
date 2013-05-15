<?php

require_once 'Narvalo/Test/SetsBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Sets;
use \Narvalo\Test\Tap;

$runner = new Tap\DefaultTapRunner(\TRUE);

$file = 't/arvalo/Test/more-bailout.php';
//$file = 't/Narvalo/Test/simple-inline.php';
//$file = 't/Narvalo/Test/more-noplan.php';
//$file = 't/Narvalo/Test/more-plan.php';
//$file = 't/Narvalo/Test/more-skipall.php';
//$file = 't/Narvalo/Test/more-bailout.php';
$file = 't/Narvalo/Test/more-complex.php';
//$file = 't/Narvalo/Test/more-throw.php';
//$file = 't/Narvalo/Test/more-autorun.php';
$file = 't/i-do-not-exist.php';

$runner->run(new Sets\FileTestSet($file));

// EOF
