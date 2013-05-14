<?php

require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Tap;

$harness = new Tap\DefaultTapHarness();
$harness->scanDirectoryAndExecute('t');
exit();
$harness->executeTestFiles(array(
  't/Narvalo/Test/more-noplan.phpt',
  't/Narvalo/Test/more-plan.phpt',
));

// EOF
