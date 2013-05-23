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
    Read  = 1,
    Write = 2;
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ FileMode

final class FileMode {
  const
    Append       = 1,
    CreateNew    = 2,
    Open         = 3,
    OpenOrCreate = 4,
    Truncate     = 5;
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ FileHandle

class FileHandle {
  private
    $_faccess,
    $_fh;

  function __construct($_path_, $_mode_, $_extended_ = \FALSE) {
    $fh = \fopen($_path_, self::_GetFileModeString($_mode_, $_extended_));
    if (\FALSE === $fh) {
      self::_ThrowOnFailedOpen($_path_, $_mode_);
    }
    $this->_fh = $fh;
    $this->_faccess = self::_GetFileAccess($_mode_, $_extended_);
  }

  function __destruct() {
    $this->cleanup_(\FALSE);
  }

  function close() {
    $this->cleanup_(\TRUE);
  }

  function canRead() {
    return $this->_faccess & FileAccess::Read;
  }

  function canWrite() {
    return $this->_faccess & FileAccess::Write;
    //return 0 === \fwrite($this->_fh, '');
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

  private static function _GetFileAccess($_mode_, $_extended_) {
    switch ($_mode_) {
    case FileMode::Append:
    case FileMode::CreateNew:
    case FileMode::OpenOrCreate:
    case FileMode::Truncate:
      return $_extended_ ? self::Write : (self::Read | self::Write);
    case FileMode::Open:
      return $_extended_ ? self::Read  : (self::Read | self::Write);
    }
  }

  private static function _GetFileModeString($_mode_, $_extended_) {
    switch ($_mode_) {
    case FileMode::Append:
      return $_extended_ ? 'a+' : 'a';
    case FileMode::CreateNew:
      return $_extended_ ? 'x+' : 'x';
    case FileMode::Open:
      return $_extended_ ? 'r+' : 'r';
    case FileMode::OpenOrCreate:
      return $_extended_ ? 'c+' : 'c';
    case FileMode::Truncate:
      return $_extended_ ? 'w+' : 'w';
    }
  }

  private static function _ThrowOnFailedOpen($_path_, $_mode_) {
    switch ($_mode_) {
    case FileMode::CreateNew:
      throw new IOException(\sprintf('Unable to create the file "%s".', $_path_));
    case FileMode::Open:
      throw new FileNotFoundException(\sprintf('Unable to open the file "%s".', $_path_));
    case FileMode::Append:
    case FileMode::OpenOrCreate:
    case FileMode::Truncate:
      throw new IOException(\sprintf('Unable to open the file "%s" for writing.', $_path_));
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ StandardError

class StandardError extends FileHandle {
  function __construct() {
    parent::__construct('php://stderr', FileMode::Truncate);
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ StandardOutput

class StandardOutput extends FileHandle {
  function __construct() {
    parent::__construct('php://stdout', FileMode::Truncate);
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ StandardInput

class StandardInput extends FileHandle {
  function __construct() {
    parent::__construct('php://stdin', FileMode::Open);
  }
}

// }}} ---------------------------------------------------------------------------------------------

// EOF
