<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More();

$t->plan(1);

$t->ok(\TRUE, 'OK');

$t->bailOut('Premature exit.');

// EOF
