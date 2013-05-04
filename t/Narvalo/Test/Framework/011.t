<?php

require_once 'Narvalo/Test.php';

use Narvalo\Test;

class TestSimpleSpec extends Test\TestSpecBase
{
    public function __construct() {
        ;
    }

    protected function RunScenario() {
        $t = new Test\Simple(1);
        $t->Assert(TRUE, 'OK');
    }
}

return TestSimpleSpec::AutoRun();

