<?php

require 'Narvalo\Test\Framework.php';

use Narvalo\Test\Framework\Test;

Test\TapEngine::UniqueInstance()->Startup();

$t = new Test\More();

$t->Plan(15);
//$t->SkipAll('TEST');

$t->Assert(TRUE, 'Passing test');
//$t->Assert(FALSE, 'Failing test');

//Test\TapRunner::UniqueInstance()->Shutdown();

$t->Pass('Passing test');
$t->Fail('Failing test');

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

//$t->CanInclude('doesnotexist.php', 'Failing include does not exist');
//$t->CanInclude('failed.php', 'Failing include failed');
//$t->CanInclude('returnfalse.php', 'Passing include returning FALSE');

$t->SubTest($t, function ($t) {
    //$t->Plan(5);
    $t->IsEqual(1, 2, 'Passing IsEqual Numeric');
    $t->Assert(TRUE, 'Passing sub test');
    $t->Assert(TRUE, 'Passing sub test');
    //$s = new Test\Simple();
    $t->Assert(TRUE, 'Passing sub test');
    $t->SubTest($t, function ($t) {
        $t->Plan(5);
        $t->IsEqual(1, 1, 'Passing IsEqual Numeric');
        $t->Assert(TRUE, 'Passing sub test');
        $t->Assert(TRUE, 'Passing sub test');
        $t->SubTest($t, function ($t) {
            $t->IsEqual(1, 1, 'Passing IsEqual Numeric');
        }, 'Sub sub sub test');
        //$s = new Test\Simple();
        $t->Assert(TRUE, 'Passing sub test');
    }, 'Sample sub subtest');
}, 'Sample subtest');

$t->SkipSubTest($t, function ($t) {
    $t->IsEqual(1, 1, 'Passing IsEqual Numeric');
    $t->Assert(TRUE, 'Passing sub test');
    $t->Assert(TRUE, 'Passing sub test');
    $t->Assert(FALSE, 'Failing sub test');
    //$t->Like(" ", "\s+}", 'Failing illformed Regex');
}, 'Sample failing subtest', 'Skipped test');

//$s = new Test\Simple();
//$t->Like(" ", "{\s+}", 'Passing Regex');

if (TRUE) {
    $t->Skip(2, 'Sample skip section');
}
else {
    $t->Assert(TRUE, 'Passing test marked as SKIP');
    $t->Assert(FALSE, 'Failing test marked as SKIP');
}

return Test\TapEngine::UniqueInstance()->Shutdown();
