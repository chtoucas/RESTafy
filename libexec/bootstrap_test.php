<?php

require_once 'Narvalo/Test/FrameworkBundle.php';

use \Narvalo\Test\Framework;

__startup();

function __startup() {
  if (!Framework\TestKernel::Bootstrapped()) {
    include_once 'Narvalo/Test/TapBundle.php';

    Framework\TestKernel::Bootstrap(new \Narvalo\Test\Tap\DefaultTapProducer(\TRUE));
  }

  Framework\TestKernel::GetSharedProducer()->startup();

  \register_shutdown_function(__NAMESPACE__.'\__shutdown');
}

function __shutdown() {
  Framework\TestKernel::GetSharedProducer()->shutdown();
}

// EOF

