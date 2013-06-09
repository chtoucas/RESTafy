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

ProveApp::Main($argv);

// -------------------------------------------------------------------------------------------------

class ProveApp {
  static function Main(array $_argv_) {
    try {
      $options = ProveOptions::Parse($_argv_);
      (new self())->run($options);
    } catch (\Exception $e) {
      self::_OnUnhandledException($e);
    }
  }

  function run(ProveOptions $_options_) {
    $stdout = IO\File::GetStandardOutput();
    $writer = new Tap\TapHarnessWriter($stdout);

    $harness = new Runner\TestHarness($writer);
    $harness->scanDirectoryAndExecute($_options_->getDirectoryPath());

    $writer->close();
    $stdout->close();
  }

  private static function _OnUnhandledException(\Exception $_e_) {
    Narvalo\Log::Fatal($_e_);
    echo $_e_->getMessage(), \PHP_EOL;
    exit(1);
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
