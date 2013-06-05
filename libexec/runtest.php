<?php
/// Usage: runtest.php [filepath]

namespace Narvalo\Test\Runner;

require_once 'NarvaloBundle.php';
require_once 'Narvalo/Test/SetsBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo;
use \Narvalo\Test\Sets;
use \Narvalo\Test\Tap;

try {
  RunTestApp::Main($argv);
} catch (\Exception $e) {
  Narvalo\Log::Fatal($e);
  echo $e->getMessage(), \PHP_EOL;
  exit(1);
}

// ------------------------------------------------------------------------------------------------

class RunTestApp {
  static function Main(array $_argv_) {
    $options  = RunTestOptions::Parse($_argv_);

    (new self())->run($options);
  }

  function run(RunTestOptions $_options_) {
    $runner = new Tap\TapRunner();
    $runner->run(new Sets\FileTestSet($_options_->getFilePath()));
  }
}

class RunTestOptions {
  private $_filePath;

  function getFilePath() {
    return $this->_filePath;
  }

  function setFilePath($_value_) {
    $this->_filePath = $_value_;
  }

  static function Parse(array $_argv_) {
    $self = new self();

    if (!\array_key_exists(1, $_argv_)) {
      throw new Narvalo\ApplicationException('You must supply the path of a file to test.');
    }
    $self->setFilePath($_argv_[1]);

    return $self;
  }
}

// EOF
