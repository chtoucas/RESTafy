<?php

require_once 'Narvalo/Test/More.php';
require_once 'Narvalo/Test/TestSuite.php';

use \Narvalo\Test;

return Test\run(function() {
  $t = new Test\More();

  $t->assert(\TRUE, 'OK');
});

