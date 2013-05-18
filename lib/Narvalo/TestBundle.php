<?php

namespace Narvalo\Test;

require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/More.php';

use \Narvalo\Test\Internal as _;

function plan($_max_) {
  _\test()->plan($_max_);
}

function ok($_test_, $_name_) {
  return _\test()->ok($_test_, $_name_);
}

function is($_got_, $_expected_, $_name_) {
  return _\test()->is($_got_, $_expected_, $_name_);
}

function isnt($_got_, $_expected_, $_name_) {
  return _\test()->isnt($_got_, $_expected_, $_name_);
}

function pass($_name_ = '') {
  return _\test()->pass($_got_, $_expected_, $_name_);
}

function fail($_name_ = '') {
  return _\test()->fail($_got_, $_expected_, $_name_);
}

function BAIL_OUT($_reason_) {
  return _\test()->bailOut($_reason_);
}

// #################################################################################################

namespace Narvalo\Test\Internal;

use \Narvalo\Test;
use \Narvalo\Test\Framework;

function test() {
  static $_Test;

  if (\NULL === $_Test) {
    __startup();

    $_Test = new Test\More();
  }

  return $_Test;
}

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

