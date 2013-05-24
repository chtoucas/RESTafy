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
  // Creational methods.

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

  //

  static function Copy($_source_, $_dest_, $_overwrite_ = \FALSE) {
    throw new Narvalo\NotImplementedException();
  }

  static function Delete($_path_) {
    throw new Narvalo\NotImplementedException();
  }

  static function Exists($_path_) {
    return \file_exists($_path_);
  }

  static function GetLastAccessTime($_path_) {
    if (\FALSE !== ($atime = \fileatime($_path_))) {
      return $atime;
    } else {
      throw new IOException('XXX');
    }
  }

  static function GetLastWriteTime($_path_) {
    if (\FALSE !== ($mtime = \filemtime($_path_))) {
      return $mtime;
    } else {
      throw new IOException('XXX');
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ FileHandle

class FileHandle {
  private
    $_fh,
    $_canRead  = \FALSE,
    $_canWrite = \FALSE;

  function __construct($_path_, $_mode_, $_extended_ = \FALSE) {
    $fh = \fopen($_path_, self::_ModeToString($_mode_, $_extended_));
    if (\FALSE === $fh) {
      self::_ThrowOnFailedOpen($_path_, $_mode_);
    }
    $this->_fh = $fh;
    $this->_setFileAccess($_mode_, $_extended_);
  }

  function __destruct() {
    $this->cleanup_(\FALSE);
  }

  function close() {
    $this->cleanup_(\TRUE);
  }

  function canRead() {
    return $this->_canRead;
  }

  function canWrite() {
    return $this->_canWrite;
  }

  function read($_length_) {
    if (!$this->_canRead) {
      throw new Narvalo\NotSupportedException('XXX');
    } else if (\feof($this->_fh)) {
      throw new IOException('You already reached EOF.');
    }

    if (FALSE !== ($result = \fread($this->_fh, $_length_))) {
      return $result;
    } else {
      throw new IOException(\sprintf('Unable to read "%s" bytes from the file.', $_length_));
    }
  }

  function readLine($_length_ = 0) {
    throw new Narvalo\NotImplementedException();
  }

  function write($_value_) {
    if (!$this->_canWrite) {
      throw new Narvalo\NotSupportedException('XXX');
    }

    if (FALSE !== ($length = \fwrite($this->_fh, $_value_))) {
      return $length;
    } else {
      throw new IOException('Unable to write to the file.');
    }
  }

  function writeLine($_value_) {
    return $this->write($_value_ . \PHP_EOL);
  }

  protected function cleanup_($_disposing_) {
    if (\NULL === $this->_fh) {
      return;
    }

    $this->_canRead  = \FALSE;
    $this->_canWrite = \FALSE;

    if (\TRUE === \fclose($this->_fh)) {
      $this->_fh = \NULL;
    } else if (!$_disposing_) {
      throw new IOException('Unable to close the file handle.');
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

  private function _setFileAccess($_mode_, $_extended_) {
    if ($_extended_) {
      $this->_canRead  = \TRUE;
      $this->_canWrite = \TRUE;
    } else {
      switch ($_mode_) {
      case FileMode::Append:
      case FileMode::CreateNew:
      case FileMode::OpenOrCreate:
      case FileMode::Truncate:
        $this->_canWrite = \TRUE; break;
      case FileMode::Open:
        $this->_canRead  = \TRUE; break;
      }
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------

// EOF

