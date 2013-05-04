<?php

require_once 'RESTafy.php';

use Narvalo\Test as t;
use RESTafy\DictionaryBorg;

t\plan(4);

// Stubs.

class Stub1 extends DictionaryBorg { }
class Stub2 extends DictionaryBorg { }
class Stub3 extends DictionaryBorg {
  protected static function & GetSharedState_() {
    static $state = array('Key' => 'Value');
    return $state;
  }
}

// AAA.

$stub1 = new Stub1();
$stub1->set('Key', 'Value1');
t\is($stub1->get('Key'), 'Value1', 'Key set.');

$stub11 = new Stub1();
$stub11->set('Key', 'Value11');
$stub12 = new Stub1();
t\is($stub12->get('Key'), 'Value11', 'State is shared.');

$stub2 = new Stub2();
$stub2->set('Key', 'Value2');
t\is($stub2->get('Key'), 'Value2', 'Distinct borgs do not share states.');

$stub3 = new Stub3();
t\is($stub3->get('Key'), 'Value', 'Shared state loaded.');

