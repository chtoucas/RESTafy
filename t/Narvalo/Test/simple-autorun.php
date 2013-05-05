<?php

require_once 'Narvalo/Test/Simple.php';
require_once 'Narvalo/Test/TestSuite.php';

use \Narvalo\Test;

class TestSuite extends Test\AbstractTestSuite {
  public function __construct() {
    ;
  }

  protected function runSuite() {
    $t = new Test\Simple(1);

    $t->assert(\TRUE, 'OK');
  }
}

return TestSuite::Run();

