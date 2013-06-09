<?php
/// Usage: prove.php [dirpath]

namespace Narvalo\Test\Runner;

require_once 'NarvaloBundle.php';
require_once 'Narvalo/IOBundle.php';
require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/RunnerBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo;
use \Narvalo\IO;
use \Narvalo\Test\Framework;
use \Narvalo\Test\Runner;
use \Narvalo\Test\Tap;

// -------------------------------------------------------------------------------------------------

class ProveApp extends Narvalo\DisposableObject {
  const
    SuccessCode = 0,
    FailureCode = 1;

  private
    $_stdout,
    $_harness;

  function __construct() {
    $this->_stdout = IO\File::GetStandardOutput();

    $outWriter = new Framework\NoopTestOutWriter();
    $errWriter = new Framework\NoopTestErrWriter();
    $engine    = new Framework\TestEngine($outWriter, $errWriter);
    $producer  = new Framework\TestProducer($engine);
    $runner    = new Runner\TestRunner($producer);
    $writer    = new Tap\TapHarnessWriter($this->_stdout);

    $producer->register();

    $this->_harness = new Runner\TestHarness($writer, $runner);
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

  protected function close_() {
    if (\NULL !== $this->_stdout) {
      $this->_stdout->close();
    }
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
