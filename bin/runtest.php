<?php

require_once 'Narvalo/Test/Framework.php';
require_once 'Narvalo/Test/Framework/Tap.php';

use Narvalo\Test\Framework\Tap;
use Narvalo\Test\Framework\TestModule;
use Narvalo\Test\Framework\TestProducer;
use Narvalo\Test\Framework\TestRunner;

$producer = new TestProducer(
    new Tap\StandardTapOutStream(TRUE), new Tap\StandardTapErrStream());
TestModule::Initialize($producer);
TestRunner::UniqInstance()->RunTest('t/Narvalo/Test/Framework/009.t');

