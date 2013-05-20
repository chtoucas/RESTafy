<?php

require_once 'NarvaloBundle.php';
require_once 'Narvalo/TestBundle.php';

use \Narvalo;
use \Narvalo\Test as t;

t\plan(4);

// Stubs.

class DictionaryBorgStub1 extends Narvalo\DictionaryBorg { }

class DictionaryBorgStub2 extends Narvalo\DictionaryBorg { }

class DictionaryBorgStub3 extends Narvalo\DictionaryBorg {
  protected static function & GetSharedState_() {
    static $state = array('Key' => 'Value');
    return $state;
  }
}

// AAA.

$stub1 = new DictionaryBorgStub1();
$stub1->set('Key', 'Value1');
t\is($stub1->get('Key'), 'Value1', 'Key set.');

$stub11 = new DictionaryBorgStub1();
$stub11->set('Key', 'Value11');
$stub12 = new DictionaryBorgStub1();
t\is($stub12->get('Key'), 'Value11', 'State is shared.');

$stub2 = new DictionaryBorgStub2();
$stub2->set('Key', 'Value2');
t\is($stub2->get('Key'), 'Value2', 'Distinct borgs do not share states.');

$stub3 = new DictionaryBorgStub3();
t\is($stub3->get('Key'), 'Value', 'Shared state loaded.');

// EOF
