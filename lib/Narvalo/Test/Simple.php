<?php

namespace Narvalo\Test;

require_once 'Narvalo/Test/FrameworkBundle.php';

use \Narvalo\Test\Framework;

/// This class should get you up to speed.
/// Once you are familiar with the TAP protocol, you should move to another Test Modules.
class Simple {
  use Framework\TestProducerAccessor;

  function __construct($_how_many_) {
    $this->getProducer()->plan($_how_many_);
  }

  function assert($_test_, $_description_) {
    return $this->getProducer()->assert($_test_, $_description_);
  }
}

// EOF
