<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More(4);

$t->pass('First test');

TODO: {
  $todo = $t->startTodo('Sample todo tests');
  $t->pass('Second passing todo test');
  $t->is(1, 2, 'Third failing todo test');
  $t->endTodo($todo);
}

$t->pass('Fourth test');

// EOF
