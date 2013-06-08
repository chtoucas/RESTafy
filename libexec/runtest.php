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

RunTestApp::Main($argv);

// -------------------------------------------------------------------------------------------------

class RunTestApp {
  const
    SuccessCode = 0,
    // NB: TAP uses 255 but in PHP this is a reserved code.
    FailureCode = 254;

  static function Main(array $_argv_) {
    try {
      $options   = RunTestOptions::Parse($_argv_);
      $exit_code = (new self())->run($options);
      exit($exit_code);
    } catch (\Exception $e) {
      self::OnUnhandledException($e);
    }
  }

  function run(RunTestOptions $_options_) {
    $stdout    = IO\File::GetStandardOutput();
    $outWriter = new Tap\TapOutWriter($stdout, \TRUE);
    $errWriter = new Tap\TapErrWriter($stdout);

    $engine    = new Framework\TestEngine($outWriter, $errWriter);
    $producer  = new Framework\TestProducer($engine);
    $producer->register();

    $runner = new Runner\TestRunner($producer);
    $result = $runner->run(new Sets\FileTestSet($_options_->getFilePath()));

    $errWriter->close();
    $outWriter->close();
    $stdout->close();

    return self::_GetExitCode($result);
  }

  private static function _GetExitCode(Framework\TestSetResult $result) {
    if ($result->runtimeErrorsCount > 0) {
      return self::FailureCode;
    } elseif ($result->passed) {
      return self::SuccessCode;
    } elseif ($result->bailedOut) {
      return self::FailureCode;
    } elseif (($count = $result->failuresCount) > 0) {
      return $count < self::FailureCode ? $count : (self::FailureCode - 1);
    } else {
      // Other kind of errors: extra tests, unattended interrupt.
      return self::FailureCode;
    }
  }

  private static function _OnUnhandledException(\Exception $_e_) {
    Narvalo\Log::Fatal($_e_);
    echo $_e_->getMessage(), \PHP_EOL;
    exit(self::FailureCode);
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
