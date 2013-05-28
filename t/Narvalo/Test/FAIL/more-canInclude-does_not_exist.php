<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More(1);

$t->canInclude('does_not_exist.php', 'File not found');

// EOF
