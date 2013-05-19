<?php

require_once 'NarvaloBundle.php';
require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo;
use \Narvalo\Test\Framework;
use \Narvalo\Test\Tap;

final class ProveTapProducer extends Tap\TapProducer {
  function __construct() {
    parent::__construct(
      new Tap\TapOutStream('php://stdout', \TRUE), new Tap\TapErrStream('php://stdout'));
  }
}

if (Framework\TestKernel::Bootstrapped()) {
  throw new Narvalo\InvalidOperationException('TestKernel was already initialized.');
}

$producer = new ProveTapProducer(\TRUE);
Framework\TestKernel::Bootstrap($producer);
$producer->startup();

\register_shutdown_function(function() {
  Framework\TestKernel::GetSharedProducer()->shutdown();
});

// EOF

