<?php
/// Usage: ./bin/runphp libexec/harness.php

require_once 'Narvalo/Test/RunnerBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Runner;
use \Narvalo\Test\Tap;

$harness = new Runner\TestHarness(new Tap\TapHarnessStream('php://stdout'));

$harness->scanDirectoryAndExecute('t');

// EOF
