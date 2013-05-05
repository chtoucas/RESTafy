<?php

require_once 'Narvalo/Test/More.php';
require_once 'Narvalo/Test/TestSpec.php';

use Narvalo\Test;

class TestSpec extends Test\AbstractTestSpec {
  private $t;

  public function __construct() {
    $this->t = new Test\More();
  }

  protected function runScenario() {
    $this->t->plan(3);
    $this->t->assert(\TRUE, 'Passing test.');
    $this->assert();
  }

  protected function assert() {
    $this->t->pass('Passing test.');
    $this->t->fail('Failing test.');
  }
}

return TestSpec::AutoRun();

