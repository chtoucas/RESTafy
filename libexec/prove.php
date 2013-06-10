<?php
/// Usage: prove.php [dirpath]

namespace Narvalo\Test\Runner;

require_once 'NarvaloBundle.php';
require_once 'Narvalo/IOBundle.php';
require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/RunnerBundle.php';
require_once 'Narvalo/Test/TapBundle.php';
require_once '_Aliens/Color2.php';

use \Narvalo;
use \Narvalo\IO;
use \Narvalo\Test\Framework;
use \Narvalo\Test\Runner;
use \Narvalo\Test\Tap;

// -------------------------------------------------------------------------------------------------

class DefaultTapHarnessWriter extends Tap\TapHarnessWriter {
  private $_color;

  function __construct() {
    parent::__construct(IO\File::GetStandardOutput());

    $this->_color = new \Console_Color2();
  }

  protected function writeError_($_value_) {
    return parent::writeError_($this->_color->convert("%r$_value_%n"));
  }
}

class ProveApp {
  const
    SuccessCode = 0,
    FailureCode = 1;

  private $_harness;

  function __construct() {
    $engine = new Framework\TestEngine(
      new Framework\NoopTestOutWriter(), new Framework\NoopTestErrWriter());
    $producer = new Framework\TestProducer($engine);

    $producer->register();

    $this->_harness
      = new Runner\TestHarness(new DefaultTapHarnessWriter(), new Runner\TestRunner($producer));
  }

  static function Main(array $_argv_) {
    $exit_code;

    try {
      $self = new self();

      $opts = ProveOptions::Parse($_argv_);
      $self->run($opts);
      $exit_code = self::SuccessCode;
    } catch (\Exception $e) {
      self::_OnUnhandledException($e);
      $exit_code = self::FailureCode;
    }

    exit($exit_code);
  }

  function run(ProveOptions $_options_) {
    $this->_harness->scanDirectoryAndExecute($_options_->getDirectoryPath());
  }

  private static function _OnUnhandledException(\Exception $_e_) {
    Narvalo\Log::Fatal($_e_);

    $stderr = IO\File::GetStandardError();
    $stderr->writeLine($_e_->getMessage());
    $stderr->close();
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

// -------------------------------------------------------------------------------------------------

ProveApp::Main($argv);

// EOF
