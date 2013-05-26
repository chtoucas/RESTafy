<?php

require_once 'Narvalo/Test/Simple.php';
require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More();

$t->pass('First test');

$t->subtest('Second test, subtest 1', function() use ($t) {
  $t->plan(3);

  $t->pass('First test in subtest (1)');

  $t->subtest('Second test, subtest 2', function() use ($t) {
    $t->plan(3);

    $t->pass('First test in subtest (2)');

    $t->subtest('Second test, subtest 3', function() use ($t) {
      $t->plan(1);
      $t->pass('First test in subtest (3)');
    });

    $t->pass('Third test in subtest (2)');
  });

  $t->pass('Third test in subtest (1)');
});

$t->pass('Third test');

// EOF
