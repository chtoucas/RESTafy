<?php

require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More();

$t->plan(15);
//$t->skipAll('TEST');

$t->assert(\TRUE, 'Passing test');
//$t->assert(\FALSE, 'Failing test');

$t->pass('Passing test');
$t->fail('Failing test');

TODO: {
  $t->startTodo('Sample todo tests');
  $t->assert(\TRUE, 'Passing test marked as TODO');
  $t->assert(\FALSE, 'Failing test marked as TODO');
  $t->endTodo();
}

$t->isEqual(1, 1, 'Passing isEqual Numeric');
$t->isEqual(\NULL, '', "Passing isEqual NULL==''");
$t->isEqual(\NULL, \NULL, 'Passing isEqual NULL==NULL');
$t->isEqual(array(1, 1), array(1, 1), 'Passing isEqual array');
$t->isEqual(new \StdClass(), new \StdClass(), 'Passing isEqual anonymous object');

$t->like(" ", "{\s+}", 'Passing Regex');

$t->canInclude('doesnotexist.php', 'Failing include does not exist');
//$t->canInclude('failed.php', 'Failing include failed');
//$t->canInclude('returnfalse.php', 'Passing include returning FALSE');

$t->subTest($t, function ($t) {
  //$t->plan(5);
  $t->isEqual(1, 2, 'Passing isEqual Numeric');
  $t->assert(\TRUE, 'Passing sub test');
  $t->assert(\TRUE, 'Passing sub test');
  //$s = new Test\Simple();
  $t->assert(\TRUE, 'Passing sub test');
  $t->subTest($t, function ($t) {
    $t->plan(5);
    $t->isEqual(1, 1, 'Passing isEqual Numeric');
    $t->assert(\TRUE, 'Passing sub test');
    $t->assert(\TRUE, 'Passing sub test');
    $t->subTest($t, function ($t) {
      $t->isEqual(1, 1, 'Passing isEqual Numeric');
    }, 'Sub sub sub test');
    //$s = new Test\Simple();
    $t->assert(\TRUE, 'Passing sub test');
  }, 'Sample sub subtest');
}, 'Sample subtest');

$t->skipSubTest($t, function ($t) {
  $t->isEqual(1, 1, 'Passing isEqual Numeric');
  $t->assert(\TRUE, 'Passing sub test');
  $t->assert(\TRUE, 'Passing sub test');
  $t->assert(\FALSE, 'Failing sub test');
  //$t->like(" ", "\s+}", 'Failing illformed Regex');
}, 'Sample failing subtest', 'Skipped test');

if (\TRUE) {
  $t->skip(2, 'Sample skip section');
}
else {
  $t->assert(\TRUE, 'Passing test marked as SKIP');
  $t->assert(\FALSE, 'Failing test marked as SKIP');
}

// EOF
