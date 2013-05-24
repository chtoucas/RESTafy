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

// {{{ ObjectType

final class ObjectType {
  const
    Unknown    = 0,
    // Simple types.
    Null       = 1,
    Boolean    = 2,
    Integer    = 3,
    Float      = 4,
    String     = 5,
    // Complex types.
    TrueArray  = 10,
    Dictionary = 11,
    Object     = 12,
    Resource   = 13;

  private function __construct() { }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ TypeName

class TypeName {
  const
    Delimiter       = '\\',
    GlobalNamespace = '\\';

  private static
    // Cf. http://www.php.net/manual/fr/language.oop5.basic.php
    $_TypeNameRegex = "/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/",
    // TODO: Check the namespace regex.
    $_NamespaceNameRegex = "/^[a-zA-Z_\x7f-\xff][\\a-zA-Z0-9_\x7f-\xff]*[a-zA-Z0-9_\x7f-\xff]$/";

  private
    $_name,
    $_namespace;

  function __construct($_name_, $_namespace_ = self::GlobalNamespace) {
    $this->_name      = $_name_;
    $this->_namespace = $_namespace_;
  }

  static function FromFullyQualifiedName($_name_) {
    throw new NotImplementedException();
  }

  static function IsWellformed($_name_) {
    return 1 === \preg_match(self::$_TypeNameRegex, $_name_);
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
    return $this->_namespace . self::Delimiter . $this->_name;
  }

  function __toString() {
    return $this->getFullyQualifiedName();
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ Type

final class Type {
  /// Return the datatype of $_value_
  ///
  /// Why create our own function? There is already gettype!
  /// According to the documentation, we should never rely on gettype...
  /// Another difference with gettype is that we return a different type
  /// for hashes (associative arrays) and real arrays.
  ///
  /// $_value_ (mixed) Any PHP structure
  /// Return a string representing a somehow extended PHP type:
  ///   - null
  ///   - boolean
  ///   - integer
  ///   - float
  ///   - string
  ///   - array
  ///   - hash
  ///   - object
  ///   - resource
  /// Return NULL if none of above.
  static function GetType($_value_) {
    if (\NULL === $_value_) {
      // Keep this on top.
      return ObjectType::Null;
    } elseif (\is_string($_value_)) {
      return ObjectType::String;
    } elseif (\is_int($_value_)) {
      return ObjectType::Integer;
    } elseif (\is_float($_value_)) {
      return ObjectType::Float;
    } elseif (\is_bool($_value_)) {
      return ObjectType::Boolean;
    } elseif (\is_array($_value_)) {
      // Faster alternative to the usual snippet:
      // empty($_value_) || \array_keys($_value_) === \range(0, \count($_value_) - 1)
      $i = 0;
      while (list($k, ) = each($_value_)) {
        if ($k !== $i) {
          return ObjectType::Dictionary;
        }
        $i++;
      }
      return ObjectType::TrueArray;
    } elseif (\is_object($_value_)) {
      return ObjectType::Object;
    } elseif (\is_resource($_value_)) {
      return ObjectType::Resource;
    } else {
      return ObjectType::Unknown;
    }
  }

  static function IsComplex($_value_) {
    return !self::IsSimple($_value_);
  }

  static function IsSimple($_value_) {
    switch ($type = self::GetType($_value_)) {
    case ObjectType::Boolean:
    case ObjectType::Integer:
    case ObjectType::Float:
    case ObjectType::String:
      return \TRUE;
    default:
      return \FALSE;
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ DynaLoader

/// WARNING: Only works if the target files do not return FALSE.
final class DynaLoader {
  const
    DirectorySeparator = '/',
    FileExtension      = '.php';

  static function LoadAndEvaluateFile($_path_) {
     eval('self::LoadFile($_path_);');
  }

  static function LoadFile($_path_) {
    if (!self::TryLoadFile($_path_)) {
      throw new RuntimeException(\sprintf('Unable to load the file: "%s".', $_path_));
    }
  }

  static function LoadBundle($_namespace_) {
    if (!self::TryLoadBundle($_typeName_)) {
      throw new RuntimeException(\sprintf('Unable to load the bundle: "%s".', $_namespace_));
    }
  }

  static function LoadType(TypeName $_typeName_) {
    if (!self::TryLoadType($_typeName_)) {
      throw new RuntimeException(\sprintf('Unable to load the type: "%s".', $_typeName_));
    }
  }

  static function TryLoadFile($_path_) {
    return self::_TryIncludeFile(self::_NormalizePath($_path_));
  }

  static function TryLoadBundle($_namespace_) {
    return self::_TryIncludeLibrary(self::_GetBundlePath($_namespace_));
  }

  static function TryLoadType(TypeName $_typeName_) {
    return self::_TryIncludeLibrary(self::_GetTypePath($_typeName_));
  }

  static function TryLoadAndEvaluateFile($_path_) {
     return eval('return self::TryLoadFile($_path_);');
  }


  // Private methods.

  private static function _NameToPath($_name_) {
    return \str_replace(TypeName::Delimiter, \DIRECTORY_SEPARATOR, $_name_) . self::FileExtension;
  }

  private static function _NormalizePath($_path_) {
    return \DIRECTORY_SEPARATOR !== self::DirectorySeparator
      ? \str_replace(self::DirectorySeparator, \DIRECTORY_SEPARATOR, $_path_)
      : $_path_;
  }

  private static function _GetBundlePath($_namespace_) {
    return self::_NameToPath($_namespace_ . 'Bundle');
  }

  private static function _GetTypePath(TypeName $_typeName_) {
    return self::_NameToPath($_typeName_->getQualifiedName());
  }

  private static function _TryIncludeFile($_path_) {
    return \FALSE !== (include $_path_);
  }

  private static function _TryIncludeLibrary($_path_) {
    if (\FALSE !== ($file = \stream_resolve_include_path($_path_))) {
      return \FALSE !== (include_once $file);
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

//class Borg {
//  protected $state_;
//
//  function __construct() {
//    $this->state_ =& static::GetSharedState_();
//  }
//
//  protected static function & GetSharedState_() {
//    throw new NotImplementedException('XXX');
//  }
//}

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

// {{{ Observer

interface Observer {
  function update(Observable $_observable_);
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ Observable

class Observable {
  private $_observers;

  function __construct() {
    $this->_observers = new \SplObjectStorage();
  }

  function attach(Observer $_observer_) {
    $this->_observers->attach($_observer_);
  }

  function detach(Observer $_observer_) {
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

// {{{ Configuration

interface Configuration {
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

  static function Initialize(Configuration $_config_) {
    if (self::$_Initialized) {
      throw new ConfigurationException('XXX');
    }
    self::$_Current = $_config_;
    self::$_Initialized = \TRUE;
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Miscs
// =================================================================================================

// Diagnostics
// ================================================================================================

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

// {{{ Cache

interface Cache {
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

// {{{ DBIException

class DBIException extends Exception { }

// }}} ---------------------------------------------------------------------------------------------

// {{{ DBI

interface DBI {
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
