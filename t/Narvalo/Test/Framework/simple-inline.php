<?php

require_once 'Narvalo/Test/Simple.php';
require_once 'Narvalo/Test/TestSpec.php';

use Narvalo\Test;

return Test\TestSpecHelper::Run(function () {
  $t = new Test\Simple(1);

  $t->assert(\TRUE, "OK");
});

