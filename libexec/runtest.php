<?php
/// Usage: bin/runphp libexec/runtest.php [filepath]

namespace Narvalo\Test\Runner;

require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/RunnerBundle.php';
require_once 'Narvalo/Test/SetsBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Framework;
use \Narvalo\Test\Sets;
use \Narvalo\Test\Tap;

RunTestApp::Main();

// ------------------------------------------------------------------------------------------------

class RunTestApp {
  private $_runner;

  function __construct(Framework\TestProducer $_producer_) {
    $this->_runner = new TestRunner($_producer_);
  }

  static function Main() {
    (new self(self::GetProducer()))->run(self::GetFilePath());
  }

  static function GetProducer() {
    // NB: This producer IS NOT compatible with prove from Test::Harness.
    return new Tap\TapProducer(
      new Tap\TapOutStream('php://stdout', \TRUE),
      new Tap\TapErrStream('php://stderr'),
      \TRUE /* register */
    );
  }

  static function GetFilePath() {
    global $argv;

    if (!\array_key_exists(1, $argv)) {
      echo 'You must supply the path of a file to test.', \PHP_EOL;
      exit(1);
    }
    return $argv[1];
  }

  function run($_filepath_) {
    $this->_runner->run(new Sets\FileTestSet($_filepath_));
  }
}

// EOF
