<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More(1);

$t->canInclude(__DIR__.'/../incs/return_false.php', 'File returns FALSE');

// EOF
