<?php

require 'Narvalo\Test\More.php';

use Narvalo\Test;

$t = new Test\More();

$t->plan(1);

$t->assert(\TRUE, 'Passing test.');

