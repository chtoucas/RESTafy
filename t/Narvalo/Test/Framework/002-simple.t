<?php

require_once 'Narvalo/Test.php';

use Narvalo\Test;

return Test\TestSpecBase::RunInline(function () {
    $t = new Test\Simple(1);

    $t->Assert(TRUE, "OK");
});

