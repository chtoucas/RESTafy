<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More();

$t->skipAll('We skip all tests');

$t->pass('First test');
$t->pass('Second test');
$t->fail('Third test');

// EOF
