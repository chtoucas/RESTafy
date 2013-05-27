<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More();

$t->canInclude('_does_not_exist.php', 'Failing include does not exist');
//$t->canInclude('_broken.php', 'Failing include failed');
//$t->canInclude('_exit.php', 'Failing include with exit');
$t->canInclude('_return_false.php', 'Passing include returning FALSE');

// EOF
