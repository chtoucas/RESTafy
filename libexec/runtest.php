<?php
/// Usage: runtest.php [filepath]

namespace Narvalo\Applications;

require_once 'NarvaloBundle.php';
require_once 'Narvalo/TermBundle.php';
require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/SetsBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo;
use \Narvalo\Term;
use \Narvalo\Test\Framework;
use \Narvalo\Test\Sets;
use \Narvalo\Test\Tap;

class RunTestCommand extends Term\Command_ {
  // NB: TAP uses 255 but in PHP this is a reserved code.
  const FailureCode = 254;

  function run() {
    $path = $this->_getTestPath();

    $runner = new Tap\TapRunner(\TRUE);
    $result = $runner->run(new Sets\FileTestSet($path));
    $runner->dispose();

    return self::_GetExitCode($result);
  }

  private static function _GetExitCode(Framework\TestSetResult $result) {
    if ($result->getRuntimeErrorsCount() > 0) {
      return self::FailureCode;
    } elseif ($result->bailedOut()) {
      return self::FailureCode;
    } elseif ($result->passed()) {
      return self::SuccessCode;
    } elseif (($count = $result->getFailuresCount()) > 0) {
      return $count < self::FailureCode ? $count : (self::FailureCode - 1);
    } else {
      // Other kind of errors: extra tests, unattended interrupt.
      return self::FailureCode;
    }
  }

  private function _getTestPath() {
    $argv = $this->getArgv_();
    if (!\array_key_exists(1, $argv)) {
      throw new Narvalo\ApplicationException('You must supply the path of a file to test.');
    }
    return $argv[1];
  }
}

// =================================================================================================

RunTestCommand::Main();

// EOF
