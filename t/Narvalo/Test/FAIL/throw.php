<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More(2);

$t->pass('First test');
$t->fail('Second test');

throw new Exception('I throw an exception');

// EOF
