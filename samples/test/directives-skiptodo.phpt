<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More(4);

$t->pass('First test');

$t->skipTodo(2, 'The reason');

$t->pass('Fourth test');

// EOF
