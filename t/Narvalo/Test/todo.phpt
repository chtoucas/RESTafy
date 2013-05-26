<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More(4);

$t->pass('First test');

TODO: {
  $t->startTodo('Sample todo tests');
  $t->pass('Second passing todo test');
  $t->fail('Third failing todo test');
  $t->endTodo();
}

$t->pass('Fourth test');

// EOF