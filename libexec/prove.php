<?php
/// Usage: prove.php [dirpath]

namespace Narvalo\Test\Runner;

require_once 'NarvaloBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo;
use \Narvalo\Test\Tap;

try {
  ProveApp::Main($argv);
} catch (\Exception $e) {
  Narvalo\Log::Fatal($e);
  echo $e->getMessage(), \PHP_EOL;
  exit(1);
}

// ------------------------------------------------------------------------------------------------

class ProveApp {
  static function Main(array $_argv_) {
    $options = ProveOptions::Parse($_argv_);

    (new self())->run($options);
  }

  function run(ProveOptions $_options_) {
    $harness = new Tap\TapHarness();
    $harness->scanDirectoryAndExecute($_options_->getDirectoryPath());
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
