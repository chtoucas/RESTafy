<?php

require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use Narvalo\Test\Tap;
use Narvalo\Test\Framework;

$producer = new Tap\DefaultTapProducer();
Framework\TestModule::Initialize($producer);

$producer->startup();

include_once 't/Narvalo/Test/more-raw.php';

$producer->shutdown();
