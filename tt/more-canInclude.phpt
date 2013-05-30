<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More(4);

$t->canInclude('Narvalo/TestBundle.php', 'Include a file in include_path');
$t->canInclude(__DIR__.'/incs/fake.php', 'Include a file by absolute path');
$t->canInclude('./tt/incs/fake.php', 'Include a file by relative path');
$t->canInclude(__DIR__.'/incs/returns_false.php', 'Include a file that returns false');

// EOF
