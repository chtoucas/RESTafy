<?php
/// Usage: prove.php [dirpath]

namespace Narvalo\Test\Runner;

require_once 'NarvaloBundle.php';
require_once 'Narvalo/IOBundle.php';
require_once 'Narvalo/Test/RunnerBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo;
use \Narvalo\IO;
use \Narvalo\Test\Runner;
use \Narvalo\Test\Tap;

try {
  ProveApp::Main($argv);
} catch (\Exception $e) {
  Narvalo\Log::Fatal($e);
  echo $e->getMessage(), \PHP_EOL;
  exit(1);
}

// -------------------------------------------------------------------------------------------------

class ProveApp {
  static function Main(array $_argv_) {
    $options = ProveOptions::Parse($_argv_);

    (new self())->run($options);
  }

  function run(ProveOptions $_options_) {
    $writer = new Tap\TapHarnessWriter(IO\File::GetStandardOutput());

    $harness = new Runner\TestHarness($writer);
    $harness->scanDirectoryAndExecute($_options_->getDirectoryPath());

    $writer->close();
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
      $self->setDirectoryPath($_argv_[1]);
    }
    return $self;
  }
}

// EOF
