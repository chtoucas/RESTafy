<?php
/// Automaticaly loaded when using bin/runphpt

namespace Narvalo\Test\Runner;

require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Tap;

bootstrap();

// ------------------------------------------------------------------------------------------------

function bootstrap() {
  $producer = Tap\TapProducer::GetDefault(\TRUE /* compatible */);
  $producer->register();

  $producer->startup();

  \register_shutdown_function(function() use ($producer) {
    $producer->shutdown();
  });
}

// EOF
