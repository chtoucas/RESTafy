<?php

require_once 'Narvalo/Test/SetsBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Sets;
use \Narvalo\Test\Tap;

$runner = new Tap\DefaultTapRunner(\TRUE);

//$file = 't/arvalo/Test/more-bailout.phpt';
//$file = 't/Narvalo/Test/simple-inline.phpt';
//$file = 't/Narvalo/Test/more-noplan.phpt';
$file = 't/Narvalo/Test/more-plan.phpt';
//$file = 't/Narvalo/Test/more-skipall.phpt';
//$file = 't/Narvalo/Test/more-bailout.phpt';
//$file = 't/Narvalo/Test/more-complex.phpt';
//$file = 't/Narvalo/Test/more-throw.phpt';
//$file = 't/Narvalo/Test/more-autorun.phpt';
//$file = 't/i-do-not-exist.phpt';

$runner->run(new Sets\FileTestSet($file));

// EOF
