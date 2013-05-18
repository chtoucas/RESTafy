<?php

namespace Narvalo\Test;

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test\Internal as _;

function plan($_max_) {
  _\mk_test()->plan($_max_);
}

function ok($_test_, $_name_) {
  return _\mk_test()->ok($_test_, $_name_);
}

function is($_got_, $_expected_, $_name_) {
  return _\mk_test()->is($_got_, $_expected_, $_name_);
}

function isnt($_got_, $_expected_, $_name_) {
  return _\mk_test()->isnt($_got_, $_expected_, $_name_);
}

function pass($_name_) {
  return _\mk_test()->pass($_got_, $_expected_, $_name_);
}

function fail($_name_) {
  return _\mk_test()->fail($_got_, $_expected_, $_name_);
}

function BAIL_OUT($_reason_) {
  return _\mk_test()->bailOut($_reason_);
}

// #################################################################################################

namespace Narvalo\Test\Internal;

use \Narvalo\Test;

function mk_test() {
  static $_Test;

  if (\NULL === $_Test) {
    $_Test = new Test\More();
  }

  return $_Test;
}

// EOF

