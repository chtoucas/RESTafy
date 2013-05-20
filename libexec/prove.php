<?php
/// Usage: bin/runphp libexec/prove.php [dirpath]

namespace Narvalo\Test\Runner;

require_once 'Narvalo/Test/RunnerBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Test\Tap;

ProveApp::Main();

// ------------------------------------------------------------------------------------------------

class ProveApp {
  private $_harness;

  function __construct(TestHarnessStream $_stream_) {
    $this->_harness = new TestHarness($_stream_);
  }

  static function Main() {
    (new self(self::GetHarnessStream()))->run(self::GetDirpath());
  }

  static function GetHarnessStream() {
    return new Tap\TapHarnessStream('php://stdout');
  }

  static function GetDirpath() {
    global $argv;

    return \array_key_exists(1, $argv) ? $argv[1] : 't';
  }

  function run($_dirpath_) {
    $this->_harness->scanDirectoryAndExecute($_dirpath_);
  }
}

// EOF
