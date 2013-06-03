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

  private function __construct() { }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ File

final class File {
  // Creational methods
  // ------------------

  /// Create the file with write-only access.
  static function Create($_path_) {
    return new FileStream($_path_, FileMode::CreateNew);
  }

  /// Open the file with read-only access
  /// and position the stream at the beginning of the file.
  static function OpenRead($_path_) {
    return new FileStream($_path_, FileMode::Open);
  }

  /// Open or create the file with write-only access
  /// and position the stream at the beginning of the file.
  static function OpenWrite($_path_) {
    return new FileStream($_path_, FileMode::OpenOrCreate);
  }

  /// Open or create the file with write-only access
  /// and position the stream at the end of the file.
  static function OpenAppend($_path_) {
    return new FileStream($_path_, FileMode::Append);
  }

  /// Open or create the file with write-only access
  /// and position the stream at the beginning of the file.
  /// WARNING: This method is destructive, the file gets truncated.
  static function OpenTruncate($_path_) {
    return new FileStream($_path_, FileMode::Truncate);
  }

  static function CreateWriter($_path_, $_append_) {
    return $_append_ ? self::OpenAppend($_path_) : self::OpenTruncate($_path_);
  }

  static function GetStandardError() {
    return self::OpenTruncate('php://stderr');
  }

  static function GetStandardOutput() {
    return self::OpenTruncate('php://stdout');
  }

  // Common file operations
  // ----------------------

  static function Copy($_source_, $_dest_, $_overwrite_ = \FALSE) {
    if (!$_overwrite_ && self::Exists($_dest_)) {
      throw new IOException(\sprintf('The file "%s" already exists.', $_dest_));
    }
    if (!\copy($_source_, $_dest_)) {
      throw new IOException(\sprintf('Unable to copy the file "%s" to "%s".', $_source_, $_dest_));
    }
  }

  static function Delete($_path_) {
    if (!\unlink($_path_)) {
      throw new IOException(\sprintf('Unable to delete the file "%s".', $_path_));
    }
  }

  /// WARNING: This method does not report on exceptional conditions
  /// like an authorization failure.
  static function Exists($_path_) {
    return \file_exists($_path_);
  }

  static function GetLastAccessTime($_path_) {
    if (\FALSE !== ($atime = \fileatime($_path_))) {
      return $atime;
    } else {
      throw new IOException(\sprintf('Unable to stat "%s" for last access time.', $_path_));
    }
  }

  static function GetLastWriteTime($_path_) {
    if (\FALSE !== ($mtime = \filemtime($_path_))) {
      return $mtime;
    } else {
      throw new IOException(\sprintf('Unable to stat "%s" for last modification time.', $_path_));
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ FileHandle

class FileHandle extends Narvalo\SafeHandle_ {
  function __construct($_handle_, $_ownsHandle_) {
    parent::__construct($_ownsHandle_);

    $this->setHandle_($_handle_);
  }

  static function Create($_handle_) {
    return new self($_handle_, \FALSE /* ownsHandle */);
  }

  static function FromPath($_path_, $_mode_) {
    $handle = \fopen($_path_, $_mode_);
    if (\FALSE === $handle) {
      return new self(Narvalo\SafeHandle_::InvalidHandleValue, \TRUE /* ownsHandle */);
    } else {
      return new self($handle, \TRUE /* ownsHandle */);
    }
  }

  protected function releaseHandle_() {
    return \FALSE !== \fclose($this->handle_);
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ FileStream

class FileStream extends Narvalo\DisposableObject {
  private
    $_fh,
    $_handle,
    $_canRead   = \FALSE,
    $_canWrite  = \FALSE,
    $_endOfLine = \PHP_EOL;

  function __construct($_path_, $_mode_, $_extended_ = \FALSE) {
    // FIXME: Binary mode?
    $fh = FileHandle::FromPath($_path_, self::_FileModeToString($_mode_, $_extended_));
    if ($fh->invalid()) {
      self::_ThrowOnFailedOpen($_path_, $_mode_);
    }
    $this->_fh = $fh;
    // NB: It is important to use a reference, otherwise the handle will not be reset
    // during the finalization of the FileHandle.
    $this->_handle =& $fh->getHandle();
    $this->_setFileAccess($_mode_, $_extended_);
  }

  function __destruct() {
    $this->dispose_(\FALSE);
  }

  function canRead() {
    return $this->_canRead;
  }

  function canWrite() {
    return $this->_canWrite;
  }

  function getEndOfLine() {
    return $this->_endOfLine;
  }

  function setEndOfLine($_value_) {
    $this->_endOfLine = $_value_;
  }

  function close() {
    $this->dispose();
  }

  function endOfFile() {
    $this->throwIfDisposed_();

    return \feof($this->_handle);
  }

  function read($_length_) {
    $this->throwIfDisposed_();

    if (!$this->_canRead) {
      throw new Narvalo\NotSupportedException('Can not read a file opened in write-only mode.');
    }

    if (FALSE !== ($result = \fread($this->_handle, $_length_))) {
      return $result;
    } else {
      throw new IOException(\sprintf('Unable to read "%s" bytes from the file.', $_length_));
    }
  }

  function write($_value_) {
    $this->throwIfDisposed_();

    if (!$this->_canWrite) {
      throw new Narvalo\NotSupportedException('Can not read a file opened in read-only mode.');
    }

    if (FALSE !== ($length = \fwrite($this->_handle, $_value_))) {
      return $length;
    } else {
      throw new IOException('Unable to write to the file.');
    }
  }

  function writeLine($_value_) {
    return $this->write($_value_ . $this->_endOfLine);
  }

  protected function close_() {
    // Reset the state of the object.
    $this->_canRead  = \FALSE;
    $this->_canWrite = \FALSE;

    // Close the underlying file handle.
    if (\NULL !== $this->_fh) {
      $this->_fh->close();
    }
  }

  // Private methods
  // ---------------

  private static function _FileModeToString($_mode_, $_extended_) {
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
