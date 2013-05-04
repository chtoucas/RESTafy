<?php

require_once 'Narvalo/Test.php';

use Narvalo\Test;

return Test\TestSpecBase::RunInline(function () {
    $t = new Test\More();

    $t->Plan(2);

    $t->Assert(TRUE, 'OK');

    $t->BailOut('Premature exit');
});

