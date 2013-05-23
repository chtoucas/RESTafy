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

// {{{ File

final class File {
  /// Create the file with write-only access.
  static function Create($_path_) {
    return new FileHandle($_path_, FileMode::CreateNew);
  }

  /// Open the file with read-only access
  /// and position the stream at the beginning of the file.
  static function OpenRead($_path_) {
    return new FileHandle($_path_, FileMode::Open);
  }

  /// Open or create the file with write-only access
  /// and position the stream at the beginning of the file.
  static function OpenWrite($_path_) {
    return new FileHandle($_path_, FileMode::OpenOrCreate);
  }

  /// Open or create the file with write-only access
  /// and position the stream at the end of the file.
  static function OpenAppend($_path_) {
    return new FileHandle($_path_, FileMode::Append);
  }

  /// Open or create the file with write-only access
  /// and position the stream at the beginning of the file.
  /// WARNING: This method is destructive, the file gets truncated.
  static function OpenTruncate($_path_) {
    return new FileHandle($_path_, FileMode::Truncate);
  }

  static function OpenStandardError() {
    return self::OpenTruncate('php://stderr');
  }

  static function OpenStandardInput() {
    return self::OpenRead('php://stdin');
  }

  static function OpenStandardOutput() {
    return self::OpenTruncate('php://stdout');
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ FileHandle

class FileHandle {
  private
    $_faccess,
    $_fh;

  function __construct($_path_, $_mode_, $_extended_ = \FALSE) {
    $fh = \fopen($_path_, self::_ModeToString($_mode_, $_extended_));
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
      return $_extended_ ? FileAccess::Write : (FileAccess::Read | FileAccess::Write);
    case FileMode::Open:
      return $_extended_ ? FileAccess::Read  : (FileAccess::Read | FileAccess::Write);
    }
  }

  private static function _ModeToString($_mode_, $_extended_) {
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

// EOF
