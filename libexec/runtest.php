<?php
/// Usage: runtest.php [filepath]

namespace Narvalo\Test\Runner;

require_once 'NarvaloBundle.php';
require_once 'Narvalo/IOBundle.php';
require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/RunnerBundle.php';
require_once 'Narvalo/Test/SetsBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo;
use \Narvalo\IO;
use \Narvalo\Test\Framework;
use \Narvalo\Test\Runner;
use \Narvalo\Test\Sets;
use \Narvalo\Test\Tap;

// -------------------------------------------------------------------------------------------------

class TapApplication extends Narvalo\DisposableObject {
  private
    $_stdout,
    $_runner;

  function __construct() {
    $this->_stdout = IO\File::GetStandardOutput();

    $outWriter = new Tap\TapOutWriter($this->_stdout, \TRUE);
    $errWriter = new Tap\TapErrWriter($this->_stdout);
    $engine    = new Framework\TestEngine($outWriter, $errWriter);
    $producer  = new Framework\TestProducer($engine);

    $producer->register();

    $this->_runner = new Runner\TestRunner($producer);
  }

  function run(Sets\ITestSet $_set_) {
    $this->throwIfDisposed_();

    return $this->_runner->run($_set_);
  }

  protected function close_() {
    if (\NULL !== $this->_stdout) {
      $this->_stdout->close();
    }
  }
}

class RunTestApp extends Narvalo\DisposableObject {
  const
    SuccessCode = 0,
    // NB: TAP uses 255 but in PHP this is a reserved code.
    FailureCode = 254;

  private $_app;

  function __construct() {
    $this->_app = new TapApplication();
  }

  static function Main(array $_argv_) {
    $exit_code;

    try {
      $self = new self();

      $opts = RunTestOptions::Parse($_argv_);
      $result = $self->run($opts);
      $exit_code = self::_GetExitCode($result);
    } catch (\Exception $e) {
      self::_OnUnhandledException($e);
      $exit_code = self::FailureCode;
    }

    exit($exit_code);
  }

  function run(RunTestOptions $_options_) {
    return $this->_app->run(new Sets\FileTestSet($_options_->getFilePath()));
  }

  protected function close_() {
    if (\NULL !== $this->_stdout) {
      $this->_runner->dispose();
    }
  }

  private static function _GetExitCode(Framework\TestSetResult $result) {
    if ($result->getRuntimeErrorsCount() > 0) {
      return self::FailureCode;
    } elseif ($result->passed()) {
      return self::SuccessCode;
    } elseif ($result->bailedOut()) {
      return self::FailureCode;
    } elseif (($count = $result->getFailuresCount()) > 0) {
      return $count < self::FailureCode ? $count : (self::FailureCode - 1);
    } else {
      // Other kind of errors: extra tests, unattended interrupt.
      return self::FailureCode;
    }
  }

  private static function _OnUnhandledException(\Exception $_e_) {
    Narvalo\Log::Fatal($_e_);

    $stderr = IO\File::GetStandardError();
    $stderr->writeLine($_e_->getMessage());
    $stderr->close();
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

// -------------------------------------------------------------------------------------------------

RunTestApp::Main($argv);

// EOF
