<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More();

$t->skipAll('Skip all tests');

$t->ok(\TRUE, 'OK');

// EOF
