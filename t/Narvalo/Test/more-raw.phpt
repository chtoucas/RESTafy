<?php

require_once 'Narvalo\Test\More.php';

use Narvalo\Test;

$t = new Test\More();

$t->plan(1);

$t->assert(\TRUE, 'Passing test.');

// EOF