<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More(1);

$t->canInclude('_fake_include.php', 'Include a file');

// EOF
