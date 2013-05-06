<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More();

$t->plan(2);

$t->assert(TRUE, 'OK');

throw new Exception("exception");

// EOF
