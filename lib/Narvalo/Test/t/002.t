<?php

require_once 'Narvalo/Test.php';

use Narvalo\Test;

return Test\TapSpecBase::RunInline(function () {
    $t = new Test\More();

    $t->Plan(1);

    $t->Assert(TRUE, 'Passing test');
});

