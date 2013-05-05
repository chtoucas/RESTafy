<?php

require_once 'Narvalo/Test/Simple.php';
require_once 'Narvalo/Test/TestSpec.php';

use Narvalo\Test;

class TestSpec extends Test\AbstractTestSpec {
  public function __construct() {
    ;
  }

  protected function runScenario() {
    $t = new Test\Simple(1);

    $t->assert(\TRUE, 'OK');
  }
}

return TestSpec::AutoRun();

