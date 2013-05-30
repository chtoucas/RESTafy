<?php
/// Usage: runtest.php [filepath]

namespace Narvalo\Test\Runner;

require_once 'NarvaloBundle.php';
require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/RunnerBundle.php';
require_once 'Narvalo/Test/SetsBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo;
use \Narvalo\Test\Framework;
use \Narvalo\Test\Sets;
use \Narvalo\Test\Tap;

try {
  RunTestApp::Main($argv);
} catch (\Exception $e) {
  RunTestApp::OnUnhandledException($e);
}

// ------------------------------------------------------------------------------------------------

class RunTestApp {
  private $_runner;

  function __construct(Framework\TestProducer $_producer_) {
    $this->_runner = new TestRunner($_producer_);
  }

  static function Main(array $_argv_) {
    $options  = RunTestOptions::Parse($_argv_);

    $producer = Tap\TapProducer::GetDefault();
    $producer->register();

    (new self($producer))->run($options);
  }

  static function OnUnhandledException(\Exception $_e_) {
    Narvalo\Log::Fatal($_e_);
    echo $_e_->getMessage(), \PHP_EOL;
    exit(1);
  }

  function run(RunTestOptions $_options_) {
    $this->_runner->run(new Sets\FileTestSet($_options_->getFilePath()));
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
