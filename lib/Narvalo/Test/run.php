<?php

namespace Narvalo\Test;

require_once 'Narvalo\Test\FrameworkBundle.php';

function run($_fun_) {
  $mod = new Framework\TestModule();
  $producer = $mod->getProducer();

  $producer->startup();

  $_fun_();

  $producer->shutdown();
}

// EOF