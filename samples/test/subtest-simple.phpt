<?php

require_once 'Narvalo/Test/More.php';
require_once 'Narvalo/Test/Simple.php';

use \Narvalo\Test;

$t = new Test\More(3);

$t->pass('First test');

$t->subtest('Second test, a subtest', function() {
  $s = new Test\Simple(1);
  $s->ok(\TRUE, 'Simple test');
});

$t->pass('Third test');

// EOF
