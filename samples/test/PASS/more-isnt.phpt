<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More();

$t->isnt(\NULL, '', 'NULL !== ""');
$t->isnt(new \StdClass(), new \StdClass(), 'Anonymous object inequality');

// EOF
