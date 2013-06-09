<?php
/// Automaticaly loaded when using bin/runphpt

namespace Narvalo\Test\Runner;

require_once 'Narvalo/IOBundle.php';
require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\IO;
use \Narvalo\Test\Framework;
use \Narvalo\Test\Tap;

bootstrap();

// ------------------------------------------------------------------------------------------------

function bootstrap() {
  $stdout = IO\File::GetStandardOutput();

  $outWriter = new Tap\TapOutWriter($stdout, \TRUE);
  $errWriter = new Tap\TapErrWriter($stdout);
  $engine    = new Framework\TestEngine($outWriter, $errWriter);
  $producer  = new Framework\TestProducer($engine);

  $producer->register();
  $producer->start();

  \register_shutdown_function(function() use ($producer, $stdout) {
    $producer->stop();
    $stdout->close();
  });
}

// EOF
