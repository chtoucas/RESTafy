<?php

require_once 'Narvalo/Test/Simple.php';
require_once 'Narvalo/Test/TestSuite.php';

use \Narvalo\Test;

return Test\run(function() {
  $t = new Test\Simple(1);

  $t->assert(\TRUE, "OK");
});

