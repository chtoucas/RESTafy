<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More();

$t->like(' ', '{\s+}', 'Simple Regex');

// EOF
