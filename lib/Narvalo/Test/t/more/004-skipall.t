<?php

require_once 'Narvalo/Test.php';

use Narvalo\Test;

return Test\TestSpecBase::RunInline(function () {
    $t = new Test\More();

    $t->SkipAll('Skip all tests');

    $t->Assert(TRUE, 'OK');
});

