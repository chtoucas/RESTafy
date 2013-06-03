<?php

namespace Narvalo;

// Core classes
// =================================================================================================

// {{{ Exception

class Exception extends \Exception {
  function __construct($_message_ = '', \Exception $_innerException_ = \NULL) {
    parent::__construct($_message_, 0 /* code */, $_innerException_);
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ ObjectDisposedException

class ObjectDisposedException extends Exception { }

// }}} ---------------------------------------------------------------------------------------------
// {{{ RuntimeException

class RuntimeException extends Exception { }

// }}} ---------------------------------------------------------------------------------------------
// {{{ InvalidOperationException

class InvalidOperationException extends Exception { }

// }}} ---------------------------------------------------------------------------------------------
// {{{ NotSupportedException

class NotSupportedException extends Exception { }

// }}} ---------------------------------------------------------------------------------------------
// {{{ ApplicationException

class ApplicationException extends Exception { }

// }}} ---------------------------------------------------------------------------------------------

// {{{ ArgumentException

class ArgumentException extends Exception {
  private $_paramName;

  function __construct($_paramName_, $_message_ = '', \Exception $_innerException_ = \NULL) {
    parent::__construct($_message_, $_innerException_);
    $this->_paramName = $_paramName_;
  }

  function getParamName() {
    return $this->_paramName;
  }

  function __toString() {
    return \sprintf('%s%sParameter name: "%s".', $this->getMessage(), \PHP_EOL, $this->_paramName);
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ ArgumentNullException

class ArgumentNullException extends ArgumentException { }

// }}} ---------------------------------------------------------------------------------------------
// {{{ KeyNotFoundException

class KeyNotFoundException extends Exception { }

// }}} ---------------------------------------------------------------------------------------------

// {{{ DataType

final class DataType {
  const
    // Simple types.
    Boolean    = 1,
    Integer    = 2,
    Float      = 3,
    String     = 4,
    // Complex types.
    RealArray  = 5,
    Hash       = 6,
    Object     = 7,
    Resource   = 8;

  /// Why create our own function? There is already gettype you say!
  /// According to the documentation, we should never rely on it...
  /// A difference with it is that we return a different type
  /// for dictionaries (associative arrays) and real arrays.
  static function Of($_value_) {
    Guard::NotNull($_value_);

    if (\is_string($_value_)) {
      return DataType::String;
    } elseif (\is_int($_value_)) {
      return DataType::Integer;
    } elseif (\is_bool($_value_)) {
      return DataType::Boolean;
    } elseif (\is_float($_value_)) {
      return DataType::Float;
    } elseif (\is_array($_value_)) {
      // Faster alternative to the usual snippet:
      // empty($_value_) || \array_keys($_value_) === \range(0, \count($_value_) - 1)
      $i = 0;
      while (list($k, ) = each($_value_)) {
        if ($k !== $i) {
          return DataType::Hash;
        }
        $i++;
      }
      return DataType::RealArray;
    } elseif (\is_object($_value_)) {
      return DataType::Object;
    } elseif (\is_resource($_value_)) {
      return DataType::Resource;
    } else {
      throw new RuntimeException(\sprintf('Unknown data type: "%s".', $_value_));
    }
  }

  static function IsPrimitive($_value_) {
    switch ($type = self::Of($_value_)) {
    case DataType::Boolean:
    case DataType::Integer:
    case DataType::Float:
    case DataType::String:
      return \TRUE;
    default:
      return \FALSE;
    }
  }

  private function __construct() { }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ Type

final class Type {
  const
    NamespaceDelimiter = '\\',
    GlobalNamespace    = '\\';

  private static
    // Cf. http://www.php.net/manual/fr/language.oop5.basic.php
    $_NameRegex = "{^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$}",
    // TODO: Check the namespace regex.
    $_NamespaceNameRegex = "{^[a-zA-Z_\x7f-\xff][\\a-zA-Z0-9_\x7f-\xff]*[a-zA-Z0-9_\x7f-\xff]$}";

  private
    $_name,
    $_namespace;

  function __construct($_name_, $_namespace_ = self::GlobalNamespace) {
    $this->_name      = $_name_;
    $this->_namespace = $_namespace_;
  }

  static function Of($_obj_) {
    $class = \get_class($_obj_);
    if (\FALSE === $class) {
      throw new ArgumentException('obj', 'XXX');
    } else {
      return $class;
    }
  }

  static function FromFullyQualifiedName($_name_) {
    throw new NotImplementedException();
  }

  static function IsWellformed($_name_) {
    return 1 === \preg_match(self::$_NameRegex, $_name_);
  }

  static function IsWellformedNamespace($_name_) {
    return 1 === \preg_match(self::$_NamespaceNameRegex,  $_name_);
  }

  function getFullyQualifiedName() {
    return self::GlobalNamespace . $this->getQualifiedName();
  }

  function getName() {
    return $this->_name;
  }

  function getNamespace() {
    return $this->_namespace;
  }

  function getQualifiedName() {
    return $this->_namespace . self::NamespaceDelimiter . $this->_name;
  }

  function __toString() {
    return $this->getFullyQualifiedName();
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ DynaLoader

/// WARNING: Only works if the target files do not return FALSE.
final class DynaLoader {
  const
    DirectorySeparator = '/',
    FileExtension      = '.php';

  /// WARNING: Does not work with includes that return FALSE.
  static function IncludeFile($_path_) {
    if (!self::TryIncludeFile($_path_)) {
      throw new RuntimeException(\sprintf('Unable to include the file: "%s".', $_path_));
    }
  }

  static function LoadLibrary($_path_) {
    if (!self::TryLoadLibrary($_path_)) {
      throw new RuntimeException(\sprintf('Unable to load the library: "%s".', $_path_));
    }
  }

  static function LoadBundle($_namespace_) {
    if (!self::TryLoadBundle($_namespace_)) {
      throw new RuntimeException(\sprintf('Unable to load the bundle: "%s".', $_namespace_));
    }
  }

  static function LoadType(Type $_type_) {
    if (!self::TryLoadType($_type_)) {
      throw new RuntimeException(\sprintf('Unable to load the type: "%s".', $_type_));
    }
  }

  /// WARNING: Does not work with includes that return FALSE.
  static function TryIncludeFile($_path_) {
    return self::_TryIncludeFile(self::_NormalizePath($_path_));
  }

  static function TryLoadLibrary($_path_) {
    return self::_TryIncludeLibrary(self::_NormalizePath($_path_));
  }

  static function TryLoadBundle($_namespace_) {
    return self::_TryIncludeLibrary(self::_GetBundlePath($_namespace_));
  }

  static function TryLoadType(Type $_type_) {
    return self::_TryIncludeLibrary(self::_GetTypePath($_type_));
  }

  // Private methods
  // ---------------

  private static function _NameToPath($_name_) {
    return \str_replace(Type::NamespaceDelimiter, \DIRECTORY_SEPARATOR, $_name_)
      . self::FileExtension;
  }

  private static function _NormalizePath($_path_) {
    return \DIRECTORY_SEPARATOR !== self::DirectorySeparator
      ? \str_replace(self::DirectorySeparator, \DIRECTORY_SEPARATOR, $_path_)
      : $_path_;
  }

  private static function _GetBundlePath($_namespace_) {
    return self::_NameToPath($_namespace_ . 'Bundle');
  }

  private static function _GetTypePath(Type $_type_) {
    return self::_NameToPath($_type_->getQualifiedName());
  }

  private static function _TryIncludeFile($_path_) {
    return \FALSE !== (include $_path_);
  }

  private static function _TryIncludeLibrary($_path_) {
    if (\FALSE !== ($file = \stream_resolve_include_path($_path_))) {
      include_once $file;
      return \TRUE;
    } else {
      return \FALSE;
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ Guard

final class Guard {
  static function NotEmpty($_value_, $_paramName_) {
    if (empty($_value_)) {
      throw new ArgumentException($_paramName_, 'Value can not be empty.');
    }
  }

  static function NotNull($_value_, $_paramName_) {
    if (\NULL === $_value_) {
      throw new ArgumentNullException($_paramName_, 'Value can not be null.');
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ IDisposable

interface IDisposable {
  function dispose();
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ DisposableObject

class DisposableObject {
  private $_disposed = \FALSE;

  final function dispose() {
    $this->dispose_(\TRUE /* disposing */);
  }

  /// Only happens when dispose() is called explicitly.
  /// - dispose all disposable fields that the object owns.
  /// - optionally reset the state of the object
  /// WARNING: This method should NEVER throw or catch an exception.
  protected function close_() {
    ;
  }

  /// This method run when the object is either disposed or finalized (if you supply a finalizer):
  /// - free all external resources hold by the object and nullify them
  /// - optionally nullify large value fields
  /// NB: In most cases, you are better of using a SafeHandle_.
  /// WARNING: This method should NEVER throw or catch an exception.
  protected function free_() {
    ;
  }

  protected function throwIfDisposed_() {
    if ($this->_disposed) {
      throw new ObjectDisposedException();
    }
  }

  final protected function dispose_($_disposing_) {
    if ($this->_disposed) {
      return;
    }

    if ($_disposing_) {
      $this->close_();
    }

    $this->free_();

    $this->_disposed = \TRUE;
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ SafeHandle_

abstract class SafeHandle_ implements IDisposable {
  const InvalidHandleValue = -1;

  protected $handle_;
  private
    $_refCount   = 0,
    $_ownsHandle = \FALSE;

  protected function __construct($_ownsHandle_) {
    $this->_ownsHandle = $_ownsHandle_;
    $this->_refCount   = 1;
  }

  final function __destruct() {
    $this->dispose_(\FALSE /* disposing */);
  }

  final function & getHandle() {
    if (0 === $this->_refCount) {
      throw new ObjectDisposedException(Type::Of($this));
    }
    return $this->handle_;
  }

  final function invalid() {
    return self::InvalidHandleValue === $this->handle_;
  }

  final function closed() {
    return 0 === $this->_refCount;
  }

  final function close() {
    $this->dispose_(\TRUE /* disposing */);
  }

  final function dispose() {
    $this->dispose_(\TRUE /* disposing */);
  }

  final function addRef() {
    if (0 === $this->_refCount) {
      throw new ObjectDisposedException(Type::Of($this));
    }
    $this->_refCount++;
  }

  final function release() {
    if (0 === $this->_refCount) {
      throw new ObjectDisposedException(Type::Of($this));
    }
    if (0 === --$this->_refCount) {
      $this->_release();
    }
  }

  abstract protected function releaseHandle_();

  final protected function setHandle_($_handle_) {
    $this->handle_ = $_handle_;
  }

  final protected function dispose_($_disposing_) {
    // If the resource has already been released.
    if (0 === $this->_refCount) {
      return;
    }

    if ($_disposing_) {
      if (0 === --$this->_refCount) {
        $this->_release();
      }
    } else {
      $this->_release();
    }
  }

  private function _release() {
    if (
      // If we don't own the handle.
      !$this->_ownsHandle
      // If the handle is invalid.
      || self::InvalidHandleValue === $this->handle_
    ) {
      return;
    }

    if (!$this->releaseHandle_()) {
      Log::Warning('Unable to release the handle.');
    }

    $this->handle_ = self::InvalidHandleValue;
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ StartStopWorkflow_

abstract class StartStopWorkflow_ {
  private $_running = \FALSE;

  protected function __construct() {
    ;
  }

  function __destruct() {
    if ($this->_running) {
      Log::Warning(\sprintf(
        '%s forcefully stopped. You either forgot to call stop() or your script exited abnormally.',
        Type::Of($this)));
    }
  }

  function running() {
    return $this->_running;
  }

  final function start() {
    if ($this->_running) {
      throw new InvalidOperationException(
        \sprintf('You can not start an already running %s.', Type::Of($this)));
    }

    $this->startCore_();

    $this->_running = \TRUE;
  }

  final function stop() {
    if ($this->_running) {
      $this->stopCore_();
      $this->_running = \FALSE;
    }
  }

  abstract protected function startCore_();

  abstract protected function stopCore_();

  protected function throwIfStopped_() {
    if (!$this->_running) {
      throw new InvalidOperationException(
        \sprintf('%s stopped. You forget to call start()?', Type::Of($this)));
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Diagnostics
// =================================================================================================

// {{{ ILogger

interface ILogger {
  function debug($_msg_);
  function error($_msg_);
  function fatal(\Exception $_e_);
  function notice($_msg_);
  function warn($_msg_);
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ LoggerLevel

final class LoggerLevel {
  const
    None      = 0x00,
    Fatal     = 0x01,
    Error     = 0x02,
    Warning   = 0x04,
    Notice    = 0x08,
    Debug     = 0x16;

  private function __construct() {
    ;
  }

  static function ToString($_level_) {
    switch ($_level_) {
    case self::Debug:
      return 'Debug';
    case self::Error:
      return 'Error';
    case self::Fatal:
      return 'Fatal';
    case self::None:
      return 'None';
    case self::Notice:
      return 'Notice';
    case self::Warning:
      return 'Warning';
    default:
      return 'Unknown';
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ Logger_

abstract class Logger_ implements ILogger {
  private $_level;

  protected function __construct($_level_) {
    $this->_level = $_level_;
  }

  abstract protected function log_($_level_, $_msg_);

  function debug($_msg_) {
    if (!$this->isEnabled_(LoggerLevel::Debug)) {
      return;
    }

    $this->log_(LoggerLevel::Debug, $_msg_);
  }

  function error($_msg_) {
    if (!$this->isEnabled_(LoggerLevel::Error)) {
      return;
    }

    $this->log_(LoggerLevel::Error, $_msg_);
  }

  function fatal(\Exception $_e_) {
    if (!$this->isEnabled_(LoggerLevel::Fatal)) {
      return;
    }

    $this->log_(LoggerLevel::Fatal, $_e_->getTraceAsString());
  }

  function notice($_msg_) {
    if (!$this->isEnabled_(LoggerLevel::Notice)) {
      return;
    }

    $this->log_(LoggerLevel::Notice, $_msg_);
  }

  function warn($_msg_) {
    if (!$this->isEnabled_(LoggerLevel::Warning)) {
      return;
    }

    $this->log_(LoggerLevel::Warning, $_msg_);
  }

  protected function isEnabled_($_level_) {
    return ($_level_ & $this->_level) === $_level_;
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ DefaultLogger

class DefaultLogger extends Logger_ {
  function __construct($_level_) {
    parent::__construct($_level_);
  }

  protected function log_($_level_, $_msg_) {
    \error_log(\sprintf('[%s] %s', LoggerLevel::ToString($_level_), $_msg_));
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ Log

final class Log {
  private static $_Logger;

  static function SetLogger(ILogger $_logger_) {
    if (\NULL !== self::$_Logger) {
      throw new InvalidOperationException('You can not set the logger twice.');
    }
    self::$_Logger = $_logger_;
  }

  static function Debug($_msg_) {
    self::_GetLogger()->debug($_msg_);
  }

  static function Error($_msg_) {
    self::_GetLogger()->error($_msg_);
  }

  static function Fatal(\Exception $_e_) {
    self::_GetLogger()->fatal($_e_);
  }

  static function Warning($_msg_) {
    self::_GetLogger()->warn($_msg_);
  }

  static function Notice($_msg_) {
    self::_GetLogger()->notice($_msg_);
  }

  private static function _GetLogger() {
    if (\NULL === self::$_Logger) {
      self::SetLogger(new DefaultLogger(
        LoggerLevel::Fatal | LoggerLevel::Error | LoggerLevel::Warning | LoggerLevel::Notice));
    }
    return self::$_Logger;
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Collections
// =================================================================================================

// {{{ Dictionary

trait Dictionary {
  private $_store = array();

  function has($_key_) {
    return \array_key_exists($_key_, $this->_store);
  }

  function get($_key_) {
    $this->_checkKey($_key_);
    return $this->_store[$_key_];
  }

  function set($_key_, $_value_) {
    $this->_store[$_key_] = $_value_;
  }

  function remove($_key_) {
    $this->_checkKey($_key_);
    unset($this->_store[$_key_]);
  }

  private function _checkKey($_key_) {
    if (!$this->has($_key_)) {
      throw new KeyNotFoundException(\sprintf('The key "%s" does not exist.', $_key_));
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Singleton pattern
// =================================================================================================

// {{{ Singleton

trait Singleton {
  private static $_Instance = \NULL;

  private function __construct() {
    $this->_initialize();
  }

  final private function __clone() {
    ;
  }

  final private function __wakeup() {
    ;
  }

  final static function UniqInstance() {
    return static::$_Instance ?: static::$_Instance = new static();
  }

  private function _initialize() { }
}

// }}} ---------------------------------------------------------------------------------------------

// Borg pattern
// =================================================================================================

// {{{ Borg

// TODO: Should extend \ArrayObject?
class DictionaryBorg {
  use Dictionary;

  function __construct() {
    $this->_store =& static::GetSharedState_();
  }

  protected static function & GetSharedState_() {
    static $state = array();
    return $state;
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Observer pattern
// =================================================================================================

// {{{ IObserver

interface IObserver {
  function update(Observable $_observable_);
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ Observable

class Observable {
  private $_observers;

  function __construct() {
    $this->_observers = new \SplObjectStorage();
  }

  function attach(IObserver $_observer_) {
    $this->_observers->attach($_observer_);
  }

  function detach(IObserver $_observer_) {
    $this->_observers->detach($_observer_);
  }

  function notify() {
    for ($i = 0, $count = \count($this->_observers); $i < $count; $i++) {
      $this->_observers[$i]->update($this);
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Provider
// =================================================================================================

// {{{ ProviderSection

class ProviderSection {
  private
    $_providerClass,
    $_providerParams;

  function __construct($_providerClass_, $_providerParams_ = \NULL) {
    $this->_providerClass  = $_providerClass_;
    $this->_providerParams = $_providerParams_;
  }

  function getProviderClass() {
    return $this->_providerClass;
  }

  function getProviderParams() {
    return $this->_providerParams;
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ ProviderHelper

final class ProviderHelper {
  static function InstantiateProvider(ProviderSection $_section_) {
    $providerClass = $_section_->getProviderClass();
    $params = $_section_->getProviderParams();

    if (\NULL === $params) {
      return new $providerClass();
    } else {
      $rc = new \ReflectionClass($providerClass);
      return $rc->newInstance($params);
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Configuration
// =================================================================================================

// {{{ ConfigurationException

class ConfigurationException extends Exception { }

// }}} ---------------------------------------------------------------------------------------------

// {{{ IConfiguration

interface IConfiguration {
  function GetSection($_sectionName_);
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ ConfigurationManager

final class ConfigurationManager {
  private static
    $_Current,
    $_Initialized = \FALSE;

  static function GetSection($_sectionName_) {
    if (!self::$_Initialized) {
      throw new ConfigurationException('XXX');
    }
    return self::$_Current->GetSection($_sectionName_);
  }

  static function Initialize(IConfiguration $_config_) {
    if (self::$_Initialized) {
      throw new ConfigurationException('XXX');
    }
    self::$_Current = $_config_;
    self::$_Initialized = \TRUE;
  }
}

// }}} ---------------------------------------------------------------------------------------------

// DI container
// =================================================================================================

// {{{ ContainerException

class ContainerException extends Exception { }

// }}} ---------------------------------------------------------------------------------------------

// {{{ ContainerBuilder

class ContainerBuilder {
  function build() {
    throw new NotImplementedException();
  }

  function register() {
    throw new NotImplementedException();
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ Container

class Container {
  function resolve() {
    throw new NotImplementedException();
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Caching
// =================================================================================================

// {{{ ICache

interface ICache {
  /// Return TRUE if cache exists, FALSE otherwise.
  /// $_id_ (string) Cache Id
  /// $_namespace_ (string) Cache namespace
  /// $_test_validity_ (boolean) Check the cache validity
  function has($_id_, $_namespace_, $_test_ = \TRUE);

  /// Return cached data on success, NULL if no available cache.
  /// $_id_ (string) Cache Id
  /// $_namespace_ (string) Cache namespace
  /// $_test_validity_ (boolean) Check the cache validity
  function get($_id_, $_namespace_, $_test_ = \TRUE);

  /// Put $_data_ into the cache.
  /// $_id_ (string) Cache Id
  /// $_namespace_ (string) Cache namespace
  /// $_data_ (string) Data to be cached
  /// Return TRUE on success, FALSE otherwise.
  function put($_id_, $_namespace_, $_data_);

  /// Delete cache.
  /// $_id_ (string) Cache Id
  /// $_namespace_ (string) Cache namespace
  /// Return TRUE on success, FALSE otherwise.
  function remove($_id_, $_namespace_);

  /// Get last modified time for cache.
  /// $_id_ (string) Cache Id
  /// $_namespace_ (string) Cache namespace
  /// Return last modified time.
  function getLastModified($_id_, $_namespace_);
}

// }}} ---------------------------------------------------------------------------------------------

// Persistence
// =================================================================================================

// {{{ DbiException

class DbiException extends Exception { }

// }}} ---------------------------------------------------------------------------------------------

// {{{ IDbi

interface IDbi extends IDisposable {
  function open();
  function close();
  //function open($_opts_);
  //function selectDb($_db_);
  //function quote($_str_);
  //function & query($_sql_);
  //function prepare($_sql_);
  //function & execute();
  //function fetchRowArray();
  //function fetchRowHash();
  //function commit();
  //function rollback();
  //function finish();
}

// }}}

// {{{ Broken

//class Slice implements \Iterator {
//  private
//    $_index   = 1,
//    $_isFirst = \TRUE,
//    $_isLast  = \TRUE,
//    $_isEmpty = \FALSE,
//    $_last    = 1,
//    $_pointer = 0,
//    $_slice;
//
//  function __construct(Iterator $_it_, $_count_, $_index_, $_max_) {
//    $this->_isEmpty = ($_count_ <= 0);
//    $index = (int)$_index_;
//    if ($index <= 0) {
//      $index = 1;
//    }
//    if ($_max_ > 0 && ($last = ceil($_count_ / $_max_)) > 1) {
//      $this->_index = min($index, $last);
//      $this->_last  = $last;
//      if ($this->_index > 1) {
//        $this->_isFirst = \FALSE;
//      }
//      if ($this->_index < $last) {
//        $this->_isLast = \FALSE;
//      }
//      $this->_slice = new LimitIterator(
//        $_it_, max(0, ($this->_index - 1) * $_max_), $_max_
//      );
//    }
//    else {
//      $this->_slice = $_it_;
//    }
//  }
//
//  function index() {
//    return $this->_index;
//  }
//
//  function last() {
//    return $this->_last;
//  }
//
//  function isLast() {
//    return $this->_isLast;
//  }
//
//  function isFirst() {
//    return $this->_isFirst;
//  }
//
//  function isEmpty() {
//    return $this->_isEmpty;
//  }
//
//  function rewind() {
//    $this->_slice->rewind();
//  }
//
//  function current() {
//    return $this->_slice->current();
//  }
//
//  function key() {
//    return $this->_slice->key();
//  }
//
//  function next() {
//    return $this->_slice->next();
//  }
//
//  function valid() {
//    return $this->_slice->valid();
//  }
//}
//
//class Pager {
//  private
//    $_itemMax,
//    $_pageCount,
//    $_pageIndex;
//
//  function __construct($_pageIndex_, $_pageCount_, $_itemMax_) {
//    $this->_itemMax   = $_itemMax_;
//    $this->_pageIndex = $_pageIndex_;
//    $this->_pageCount = $_pageCount_;
//  }
//
//  static function Create($_pageIndex_, $_itemCount_, $_itemMax_) {
//    if (\NULL === $_itemCount_) {
//      $pageCount = 1;
//      $pageIndex = 1;
//    } else {
//      $pageCount
//        = 1 + ($_itemCount_ - $_itemCount_ % $_itemMax_) / $_itemMax_;
//      $pageIndex = \min(\max(1, $_pageIndex_), $pageCount);
//    }
//
//    return new self($pageIndex, $pageCount, $_itemMax_);
//  }
//
//  function isFirstPage() {
//    return 1 === $this->_pageIndex;
//  }
//
//  function isLastPage() {
//    return $this->_pageCount === $this->_pageIndex;
//  }
//
//  function getStartIndex() {
//    return ($this->_pageIndex - 1) * $this->_itemMax;
//  }
//}

// }}} ---------------------------------------------------------------------------------------------

// EOF
