<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More();

$t->canInclude('does_not_exist.php', 'Failing include');
$t->canInclude(__DIR__.'/../incs/broken.php', 'Failing include failed');
$t->canInclude(__DIR__.'/../incs/exit.php', 'Failing include with exit');
$t->canInclude(__DIR__.'/../incs/return_false.php', 'Failing include a file returning FALSE');

// EOF
