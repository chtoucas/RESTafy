<?php

namespace Narvalo\IO;

require_once 'NarvaloBundle.php';

use \Narvalo;

// {{{ IOException

class IOException extends Narvalo\Exception { }

// }}} ---------------------------------------------------------------------------------------------
// {{{ FileNotFoundException

class FileNotFoundException extends IOException { }

// }}}

// {{{ FileAccess

final class FileAccess {
  const
    Read      = 1,
    Write     = 2;
    //ReadWrite = 1 | 2;
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ FileMode

final class FileMode {
  const
    CreateNew    = 1,
    Create       = 2,
    Open         = 3,
    OpenOrCreate = 4,
    Truncate     = 5,
    Append       = 6;
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ FileHandle

class FileHandle {
  private $_fh;

  function __construct($_path_, $_mode_) {
    $fh = \fopen($_path_, $_mode_);
    if (\FALSE === $fh) {
      throw new IOException(\sprintf('Unable to open "%s" for writing.', $_path_));
    }
    $this->_fh = $fh;
  }

  function __destruct() {
    $this->cleanup_(\FALSE);
  }

  function close() {
    $this->cleanup_(\TRUE);
  }

  function canWrite() {
    return 0 === \fwrite($this->_fh, '');
  }

  function write($_value_) {
    return \fwrite($this->_fh, $_value_);
  }

  function writeLine($_value_) {
    return $this->write($_value_ . \PHP_EOL);
  }

  protected function cleanup_($_disposing_) {
    if (\NULL === $this->_fh) {
      return;
    }
    if (\TRUE === \fclose($this->_fh)) {
      $this->_fh = \NULL;
    } else if (!$_disposing_) {
      throw new IOException('Unable to close the file handle.');
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ StandardError

class StandardError extends FileHandle {
  function __construct() {
    parent::__construct('php://stderr', 'w');
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ StandardOutput

class StandardOutput extends FileHandle {
  function __construct() {
    parent::__construct('php://stdout', 'w');
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ StandardInput

class StandardInput extends FileHandle {
  function __construct() {
    parent::__construct('php://stdin', 'r');
  }
}

// }}} ---------------------------------------------------------------------------------------------

// EOF
