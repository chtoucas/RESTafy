<?php

require_once 'Narvalo/Test/More.php';
require_once 'Narvalo/Test/Simple.php';

use \Narvalo\Test;

$t = new Test\More(3);

$t->pass('First test');

$t->subtest('Second test, a subtest', function() {
  $t = new Test\More(3);
  $t->pass('First test in subtest');
  $t->bailOut('Premature exit in a subtest.');
  $t->pass('Second test in subtest');
  $t->pass('Third test in subtest');
});

$t->pass('Third test');

// EOF
