<?php

require_once 'Narvalo/Test/More.php';
require_once 'Narvalo/Test/TestSpec.php';

use Narvalo\Test;

return Test\TestSpecHelper::Run(function () {
  $t = new Test\More();

  $t->skipAll('Skip all tests');

  $t->assert(\TRUE, 'OK');
});

