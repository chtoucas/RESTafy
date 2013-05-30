<?php

require_once 'Narvalo/Test/More.php';
require_once 'Narvalo/Test/Simple.php';

use \Narvalo\Test;

$t = new Test\More(3);

$t->pass('First test');

$t->subtest('Second test, a subtest', function() {
  $t = new Test\More(1);
  $t->pass('First test');
});

$t->pass('Third test');

// EOF
