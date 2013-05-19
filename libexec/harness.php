<?php

require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Tap;

$harness = new Tap\TapHarness(new Tap\TapHarnessStream('php://stdout'));

$harness->scanDirectoryAndExecute('t');

exit();

$harness->executeFiles(array(
  't/Narvalo/Test/more-noplan.phpt',
  't/Narvalo/Test/more-plan.phpt',
));

// EOF
