<?php
/// Automaticaly loaded when you use ./bin/runphpt.

require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Framework;
use \Narvalo\Test\Tap;

// NB: This producer is compatible with prove from Test::Harness.
$producer = new Tap\TapProducer(
  new Tap\TapOutStream('php://stdout', \TRUE),
  new Tap\TapErrStream('php://stdout'),
  \TRUE /* register */
);

$producer->startup();

\register_shutdown_function(function() {
  Framework\TestKernel::GetSharedProducer()->shutdown();
});

// EOF

