<?php

namespace Narvalo\Test;

require_once 'Narvalo/Test/FrameworkBundle.php';

use \Narvalo\Test\Framework;

class Simple extends Framework\TestModule {
  function __construct($_how_many_ = \NULL) {
    parent::__construct();

    if (\NULL !== $_how_many_) {
      $this->plan($_how_many_);
    }
  }

  function plan($_how_many_) {
    $this->getProducer()->plan($_how_many_);
  }

  function ok($_test_, $_name_) {
    return $this->getProducer()->assert($_test_, $_name_);
  }
}

// EOF
