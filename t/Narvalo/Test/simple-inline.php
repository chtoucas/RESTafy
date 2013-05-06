<?php

require_once 'Narvalo/Test/Simple.php';

use \Narvalo\Test;

$t = new Test\Simple(1);

$t->assert(\TRUE, "OK");

// EOF
