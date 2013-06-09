<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More(3);

$t->pass('First test');

$t->subtest('Second test, a subtest', function() use ($t) {
  $t->plan(2);
  $t->pass('First test in subtest');
  $t->pass('Second subtest in subtest');
});

$t->pass('Third test');

// EOF
