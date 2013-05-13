<?php

require_once 'Narvalo/Test/Simple.php';
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
  $t->pass('Passing test marked as TO-DO');
  $t->fail('Failing test marked as TO-DO');
  $t->endTodo();
}

$t->equal(1, 1, 'Passing equal Numeric');
$t->equal(\NULL, '', "Passing equal NULL==''");
$t->equal(\NULL, \NULL, 'Passing equal NULL==NULL');
$t->equal(array(1, 1), array(1, 1), 'Passing equal array');
$t->equal(new \StdClass(), new \StdClass(), 'Failing equal anonymous object');

$t->like(" ", "{\s+}", 'Passing Regex');

$t->canInclude('doesnotexist.php', 'Failing include does not exist');
//$t->canInclude('failed.php', 'Failing include failed');
//$t->canInclude('returnfalse.php', 'Passing include returning FALSE');

$t->subTest(function() use ($t) {
  //$t->plan(5);
  $t->equal(1, 2, 'Passing equal Numeric');
  $t->pass('Passing sub test');
  $t->pass('Passing sub test');
  //$s = new Test\Simple(1);
  //$s->assert(\TRUE, 'Passing sub test');
  $t->subTest(function() use ($t) {
    $t->plan(5);
    $t->equal(1, 1, 'Passing equal Numeric');
    $t->pass('Passing sub test');
    $t->pass('Passing sub test');
    $t->subTest(function() use ($t) {
      $t->equal(1, 1, 'Passing equal Numeric');
    }, 'Sub sub sub test');
    //$s = new Test\Simple();
    $t->assert(\TRUE, 'Passing sub test');
  }, 'Sample sub subtest');
}, 'Sample subtest');

$t->skipSubTest(function() use ($t) {
  $t->equal(1, 1, 'Passing equal Numeric');
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
