<?php

require_once 'Narvalo/Test/Simple.php';
require_once 'Narvalo/Test/TestSuite.php';

use \Narvalo\Test;

class MySimpleTestSuite extends Test\TestSuite {
  static function Tests() {
    $t = new Test\Simple(1);

    $t->assert(\TRUE, 'OK');
  }
}

MySimpleTestSuite::Run();

// EOF