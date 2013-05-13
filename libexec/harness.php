<?php

require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Tap;

$harness = new Tap\DefaultTapHarness();
$harness->scanDirectoryAndExecute('t');

// EOF
