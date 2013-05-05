<?php

namespace Narvalo\Test;

require_once 'Narvalo\Test\FrameworkBundle.php';

/// This class should get you up to speed.
/// Once you are familiar with the TAP protocol, you should move
/// to one of the other Test Modules.
class Simple extends Framework\TestModule {
  function __construct($_how_many_) {
    parent::__construct();
    $this->getProducer()->plan($_how_many_);
  }

  function assert($_test_, $_description_) {
    return $this->getProducer()->assert($_test_, $_description_);
  }
}

// EOF
