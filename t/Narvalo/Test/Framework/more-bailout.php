<?php

require_once 'Narvalo/Test/More.php';
require_once 'Narvalo/Test/TestSpec.php';

use Narvalo\Test;

return Test\TestSpecHelper::Run(function () {
  $t = new Test\More();

  $t->plan(2);

  $t->assert(TRUE, 'OK');

  $t->bailOut('Premature exit');
});

