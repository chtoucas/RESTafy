<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More();

$t->skipAll('We are going to skip all tests');

$t->pass('First test');
$t->pass('Second test');
$t->fail('Third test');

// EOF
