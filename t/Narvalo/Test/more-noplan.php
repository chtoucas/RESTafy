<?php

require_once 'Narvalo/Test/run.php';
require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

return Test\run(function() {
  $t = new Test\More();

  $t->assert(\TRUE, 'OK');
});

