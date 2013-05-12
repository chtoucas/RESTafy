<?php

exit('broken');

require_once 'Narvalo/Test/Simple.php';
require_once 'Narvalo/Test/TestSuite.php';

use \Narvalo\Test;

class MySimpleTestSuite extends Test\AbstractTestSuite {
  function execute() {
    $t = new Test\Simple(1);

    $t->assert(\TRUE, 'OK');
  }
}

// EOF
