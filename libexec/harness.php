<?php
/// Usage:
///   ./bin/runphp libexec/harness.php

require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Tap;

$harness = new Tap\TapHarness(new Tap\TapHarnessStream('php://stdout'));

$harness->scanDirectoryAndExecute('t');

// EOF
