<?php

require_once 'Narvalo/Test/Simple.php';
require_once 'Narvalo/Test/More.php';

use \Narvalo\Test;

$t = new Test\More();

$t->plan(15);
//$t->skipAll('TEST');

$t->ok(\TRUE, 'Passing test');
//$t->ok(\FALSE, 'Failing test');

$t->pass('Passing test');
$t->fail('Failing test');

TODO: {
  $t->startTodo('Sample todo tests');
  $t->pass('Passing test marked as TO-DO');
  $t->fail('Failing test marked as TO-DO');
  $t->endTodo();
}

$t->is(1, 1, 'Passing equal Numeric');
$t->is(\NULL, '', "Passing equal NULL==''");
$t->is(\NULL, \NULL, 'Passing equal NULL==NULL');
$t->is(array(1, 1), array(1, 1), 'Passing equal array');
$t->is(new \StdClass(), new \StdClass(), 'Failing equal anonymous object');

$t->like(" ", "{\s+}", 'Passing Regex');

$t->canInclude('doesnotexist.php', 'Failing include does not exist');
//$t->canInclude('failed.php', 'Failing include failed');
//$t->canInclude('returnfalse.php', 'Passing include returning FALSE');

$t->subtest(function() use ($t) {
  //$t->plan(5);
  $t->is(1, 2, 'Passing equal Numeric');
  $t->pass('Passing sub test');
  $t->pass('Passing sub test');
  //$s = new Test\Simple(1);
  //$s->ok(\TRUE, 'Passing sub test');
  $t->subtest(function() use ($t) {
    $t->plan(5);
    $t->is(1, 1, 'Passing equal Numeric');
    $t->pass('Passing sub test');
    $t->pass('Passing sub test');
    $t->subtest(function() use ($t) {
      $t->is(1, 1, 'Passing equal Numeric');
    }, 'Sub sub sub test');
    //$s = new Test\Simple();
    $t->ok(\TRUE, 'Passing sub test');
  }, 'Sample sub subtest');
}, 'Sample subtest');

$t->skipSubtest(function() use ($t) {
  $t->is(1, 1, 'Passing equal Numeric');
  $t->pass('Passing first sub test');
  $t->pass('Passing second sub test');
  $t->fail('Failing sub test');
  //$t->like(" ", "\s+}", 'Failing illformed Regex');
}, 'Sample failing subtest', 'Skipped test');

if (\TRUE) {
  $t->skip(2, 'Sample skip section');
}
else {
  $t->pass('Passing test marked as SKIP');
  $t->fail('Failing test marked as SKIP');
}

// EOF
