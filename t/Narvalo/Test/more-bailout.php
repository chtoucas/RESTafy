<?php

require_once 'Narvalo/Test/run.php';
require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

return Test\run(function() {
  $t = new Test\More();

  $t->plan(2);

  $t->assert(TRUE, 'OK');

  $t->bailOut('Premature exit');
});

