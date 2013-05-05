<?php

//require 'Narvalo\Test\Framework\Tap.php';
require 'Narvalo\Test\More.php';
//require 'Narvalo\Test\TestSpec.php';

use Narvalo\Test;
//use Narvalo\Test\Framework\Tap;

//Tap\TapRunner::UniqInstance();
//Test\TestSpec::Begin();

$t = new Test\More();

$t->plan(1);

$t->assert(\TRUE, 'Passing test');

//Test\TestSpec::End();
//return Tap\TapRunner::UniqInstance()->shutdown();
