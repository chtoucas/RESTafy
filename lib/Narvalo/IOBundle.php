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
  private $_handle;

  function __construct($_path_, $_mode_) {
    $handle = \fopen($_path_, $_mode_);
    if (\FALSE === $handle) {
      throw new IOException(\sprintf('Unable to open "%s" for writing.', $_path_));
    }
    $this->_handle = $handle;
  }

  function __destruct() {
    $this->cleanup_(\FALSE);
  }

  function close() {
    $this->cleanup_(\TRUE);
  }

  function canWrite() {
    return 0 === \fwrite($this->_handle, '');
  }

  function write($_value_) {
    return \fwrite($this->_handle, $_value_);
  }

  function writeLine($_value_) {
    return $this->write($_value_ . \PHP_EOL);
  }

  protected function cleanup_($_disposing_) {
    if (\NULL === $this->_handle) {
      return;
    }
    if (\TRUE === \fclose($this->_handle)) {
      $this->_handle = \NULL;
    } else if (!$_disposing_) {
      throw new IOException('Unable to close the handle.');
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
