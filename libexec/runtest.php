<?php

require_once 'Narvalo/Test/SetsBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Sets;
use \Narvalo\Test\Tap;

class DefaultTestRunner extends Tap\TapRunner {
  function __construct() {
    parent::__construct(
      new Tap\TapProducer(
        new Tap\TapOutStream('php://stdout', \TRUE),
        new Tap\TapErrStream('php://stderr'),
        \TRUE /* register */
      )
    );
  }
}

$runner = new DefaultTestRunner();

//$file = 't/arvalo/Test/more-bailout.phpt';
//$file = 't/Narvalo/Test/simple-inline.phpt';
//$file = 't/Narvalo/Test/more-noplan.phpt';
//$file = 't/Narvalo/Test/more-plan.phpt';
//$file = 't/Narvalo/Test/more-skipall.phpt';
//$file = 't/Narvalo/Test/more-bailout.phpt';
//$file = 't/Narvalo/Test/more-complex.phpt';
//$file = 't/Narvalo/Test/more-throw.phpt';
$file = 't/Narvalo/Test/more-raw.phpt';
//$file = 't/Narvalo/Test/more-autorun.phpt';
//$file = 't/i-do-not-exist.phpt';

$runner->run(new Sets\FileTestSet($file));

// EOF
