<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More(1);

$t->canInclude(__DIR__.'/incs/fake.php', 'Include a file');

// EOF
