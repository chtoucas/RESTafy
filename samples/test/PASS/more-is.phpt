<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More(10);

$t->is(\NULL, \NULL, 'NULL');
$t->is(\TRUE, \TRUE, 'True');
$t->is(\FALSE, \FALSE, 'False');
$t->is(1, 1, 'Integer');
$t->is(1.1, 1.1, 'Float');
$t->is('', '', 'Empty string');
$t->is('String', 'String', 'String');
$t->is('€éàù', '€éàù', 'UTF8 string');
$t->is(array(), array(), 'Empty array');
$t->is(array(1, 2), array(1, 2), 'Array');

// EOF
