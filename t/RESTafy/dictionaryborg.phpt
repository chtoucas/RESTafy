<?php

require_once 'RESTafy.php';

use Test as t;
use RESTafy\DictionaryBorg;

t\plan(3);

// Stubs.

class Stub1 extends DictionaryBorg { }
class Stub2 extends DictionaryBorg { }

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

