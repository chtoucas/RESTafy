<?php

require_once 'Narvalo/Test/Framework.php';

use Narvalo\Test\Framework;

return Test\TestSpecBase::RunInline(function () {
    $t = new Test\More();

    $t->Plan(1);

    $t->Assert(TRUE, 'OK');
});

