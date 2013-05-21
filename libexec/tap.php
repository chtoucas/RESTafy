<?php
/// Automaticaly loaded when using bin/runphpt

namespace Narvalo\Test\Runner;

require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Framework;
use \Narvalo\Test\Tap;

bootstrap();

// ------------------------------------------------------------------------------------------------

function bootstrap() {
  // NB: This producer IS compatible with prove from Test::Harness.
  $producer = new Tap\TapProducer(
    new Tap\TapOutStream('php://stdout', \TRUE),
    new Tap\TapErrStream('php://stdout')
  );

  $producer->startup();

  \register_shutdown_function(function() use ($producer) {
    $producer->shutdown();
  });
}

// EOF
