<?php
/// Usage: prove.php [dirpath]

namespace Narvalo\Applications;

require_once 'Narvalo/TermBundle.php';
require_once 'Narvalo/Test/TapBundle.php';

use \Narvalo\Term;
use \Narvalo\Test\Tap;

class ProveCmd extends Term\CmdBase {
  const DefaultDirectoryPath = 't';

  function run() {
    $path = $this->_getDirectoryPath();

    $harness = new Tap\TapHarness();
    $harness->scanDirectoryAndExecute($path);
    $harness->dispose();

    return self::SUCCESS_CODE;
  }

  private function _getDirectoryPath() {
    $argv = $this->getArgv_();
    return \array_key_exists(1, $argv) ? $argv[1] : self::DefaultDirectoryPath;
  }
}

// =================================================================================================

ProveCmd::Main();

// EOF
