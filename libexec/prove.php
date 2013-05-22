<?php
/// Usage: prove.php [dirpath]

namespace Narvalo\Test\Runner;

require_once 'Narvalo/Test/RunnerBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Tap;

ProveApp::Main($argv);

// ------------------------------------------------------------------------------------------------

class ProveApp {
  private $_harness;

  function __construct(TestHarnessStream $_stream_) {
    $this->_harness = new TestHarness($_stream_);
  }

  static function Main(array $_argv_) {
    $options = ProveOptions::Parse($_argv_);
    $stream  = Tap\TapHarnessStream::GetDefault();

    (new self($stream))->run($options);
  }

  function run(ProveOptions $_options_) {
    $this->_harness->scanDirectoryAndExecute($_options_->getDirectoryPath());
  }
}

class ProveOptions {
  private $_directoryPath = 't';

  function getDirectoryPath() {
    return $this->_directoryPath;
  }

  function setDirectoryPath($_value_) {
    $this->_directoryPath = $_value_;
  }

  static function Parse(array $_argv_) {
    $self = new self();
    if (\array_key_exists(1, $_argv_)) {
      $self>setDirectoryPath($_argv_[1]);
    }
    return $self;
  }
}

// EOF
