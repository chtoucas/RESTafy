<?php

require_once 'Narvalo/Test.php';

use Narvalo\Test;

class SampleTestSpec extends Test\TestSpecBase
{
    private $t;

    public function __construct() {
        $this->t = new Test\More();
    }

    protected function RunScenario() {
        $this->t->Plan(3);
        $this->t->Assert(TRUE, 'Passing test');
        $this->Assert();
    }

    protected function Assert() {
        $this->t->Pass('Passing test');
        $this->t->Fail('Failing test');
    }
}

return SampleTestSpec::AutoRun();

return Test\TapSpecBase::RunInline(function () {
    TODO: {
        $t->StartTodo('Sample todo tests');
        $t->Assert(TRUE, 'Passing test marked as TODO');
        $t->Assert(FALSE, 'Failing test marked as TODO');
        $t->EndTodo();
    }

    $t->IsEqual(1, 1, 'Passing IsEqual Numeric');
    $t->IsEqual(NULL, '', "Passing IsEqual NULL==''");
    $t->IsEqual(NULL, NULL, 'Passing IsEqual NULL==NULL');
    $t->IsEqual(array(1, 1), array(1, 1), 'Passing IsEqual array');
    $t->IsEqual(new StdClass(), new StdClass(), 'Passing IsEqual anonymous object');

    $t->Like(" ", "{\s+}", 'Passing Regex');

    $t->CanInclude('doesnotexist.php', 'Failing include does not exist');

    $t->SubTest($t, function ($t) {
        $t->IsEqual(1, 2, 'Passing IsEqual Numeric');
        $t->Assert(TRUE, 'Passing sub test');
        $t->Assert(TRUE, 'Passing sub test');
        $t->Assert(TRUE, 'Passing sub test');
        $t->SubTest($t, function ($t) {
            $t->Plan(5);
            $t->IsEqual(1, 1, 'Passing IsEqual Numeric');
            $t->Assert(TRUE, 'Passing sub test');
            $t->Assert(TRUE, 'Passing sub test');
            $t->SubTest($t, function ($t) {
                $t->IsEqual(1, 1, 'Passing IsEqual Numeric');
            }, 'Sub sub sub test');
            $t->Assert(TRUE, 'Passing sub test');
        }, 'Sample sub subtest');
    }, 'Sample subtest');

    $t->SkipSubTest($t, function ($t) {
        $t->IsEqual(1, 1, 'Passing IsEqual Numeric');
        $t->Assert(TRUE, 'Passing sub test');
        $t->Assert(TRUE, 'Passing sub test');
        $t->Assert(FALSE, 'Failing sub test');
    }, 'Sample failing subtest', 'Skipped test');

    if (TRUE) {
        $t->Skip(2, 'Sample skip section');
    }
    else {
        $t->Assert(TRUE, 'Passing test marked as SKIP');
        $t->Assert(FALSE, 'Failing test marked as SKIP');
    }
});
