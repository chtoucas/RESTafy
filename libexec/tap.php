<?php

require_once 'NarvaloBundle.php';
require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo;
use \Narvalo\Test\Framework;
use \Narvalo\Test\Tap;

class ProveTapProducer extends Tap\TapProducer {
  function __construct() {
    parent::__construct(
      new Tap\TapOutStream('php://stdout', \TRUE),
      new Tap\TapErrStream('php://stdout'),
      \TRUE /* register */
    );
  }
}

$producer = new ProveTapProducer();
$producer->startup();

\register_shutdown_function(function() {
  Framework\TestKernel::GetSharedProducer()->shutdown();
});

// EOF

