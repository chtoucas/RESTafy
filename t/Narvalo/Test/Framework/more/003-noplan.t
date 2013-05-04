<?php

require_once 'Narvalo/Test.php';

use Narvalo\Test;

return Test\TestSpecBase::RunInline(function () {
    $t = new Test\More();

    $t->Assert(TRUE, 'OK');
});

