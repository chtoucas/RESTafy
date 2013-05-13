<?php

namespace Narvalo;

//const VERSION = '%%VERSION%%';

// Core classes.
// #################################################################################################

// {{{ Exception

class Exception extends \Exception {
  function __construct($_message_ = '', \Exception $_innerException_ = \NULL) {
    parent::__construct($_message_, 0, $_innerException_);
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ ArgumentException

class ArgumentException extends Exception {
  private $_paramName;

  function __construct($_paramName_, $_message_ = '', \Exception $_innerException_ = \NULL) {
    $this->_paramName = $_paramName_;
    parent::__construct($_message_, $_innerException_);
  }

  function getParamName() {
    return $this->_paramName;
  }

  function __toString() {
    return \sprintf('%s%sParameter name: "%s".', $this->getMessage(), \PHP_EOL, $this->_paramName);
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ RuntimeException

class RuntimeException extends Exception { }

// }}} ---------------------------------------------------------------------------------------------
// {{{ FileNotFoundRuntimeException

class FileNotFoundRuntimeException extends RuntimeException { }

// }}} ---------------------------------------------------------------------------------------------
// {{{ ArgumentNullException

class ArgumentNullException extends ArgumentException { }

// }}} ---------------------------------------------------------------------------------------------
// {{{ InvalidOperationException

class InvalidOperationException extends Exception { }

// }}} ---------------------------------------------------------------------------------------------
// {{{ KeyNotFoundException

class KeyNotFoundException extends Exception { }

// }}} ---------------------------------------------------------------------------------------------

// {{{ ObjectType

final class ObjectType {
  const
    UNKNOWN   = 0,
    // Simple types.
    NULL      = 1,
    BOOLEAN   = 2,
    INTEGER   = 3,
    FLOAT     = 4,
    STRING    = 5,
    // Complex types.
    REAL_ARRAY = 10,
    HASH       = 11,
    OBJECT     = 12,
    RESOURCE   = 13;
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ TypeName

class TypeName {
  const
    DELIMITER        = '\\',
    GLOBAL_NAMESPACE = '\\';

  private static
    // Cf. http://www.php.net/manual/fr/language.oop5.basic.php
    $_TypeNameRegex = "/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/",
    // TODO: Check the namespace regex.
    $_NamespaceNameRegex = "/^[a-zA-Z_\x7f-\xff][\\a-zA-Z0-9_\x7f-\xff]*[a-zA-Z0-9_\x7f-\xff]$/";

  private
    /// \var string
    $_name,
    /// \var string
    $_namespace;

  function __construct($_name_, $_namespace_ = self::GLOBAL_NAMESPACE) {
    $this->_name      = $_name_;
    $this->_namespace = $_namespace_;
  }

  /// \return bool.
  static function IsWellformed($_name_) {
    return 1 === \preg_match(self::$_TypeNameRegex, $_name_);
  }

  /// \return bool.
  static function IsWellformedNamespace($_name_) {
    return 1 === \preg_match(self::$_NamespaceNameRegex,  $_name_);
  }

  /// \return string.
  function getFullyQualifiedName() {
    return self::DELIMITER . $this->getQualifiedName();
  }

  /// \return string.
  function getName() {
    return $this->_name;
  }

  /// \return string.
  function getQualifiedName() {
    return $this->_namespace . self::DELIMITER . $this->_name;
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ Type

final class Type {
  /// \brief Return the datatype of $_value_
  ///
  /// Why create our own function? There is already gettype!
  /// According to the documentation, we should never rely on gettype...
  /// Another difference with gettype is that we return a different type
  /// for hashes (associative arrays) and real arrays.
  ///
  /// \param $_value_ (mixed) Any PHP structure
  /// \return A string representing a somehow extended PHP type:
  ///    - null
  ///    - boolean
  ///    - integer
  ///    - float
  ///    - string
  ///    - array
  ///    - hash
  ///    - object
  ///    - resource
  /// \return NULL if none of above.
  static function GetType($_value_) {
    if (\NULL === $_value_) {
      // Keep this on top.
      return ObjectType::NULL;
    }
    elseif (\is_string($_value_)) {
      return ObjectType::STRING;
    }
    elseif (\is_int($_value_)) {
      return ObjectType::INTEGER;
    }
    elseif (\is_float($_value_)) {
      return ObjectType::FLOAT;
    }
    elseif (\is_bool($_value_)) {
      return ObjectType::BOOLEAN;
    }
    elseif (\is_array($_value_)) {
      // Much faster alternative to the usual snippet:
      // array_keys($_value_) === range(0, count($_value_) - 1)
      // || empty($_value_)
      $i = 0;
      while (list($k, ) = each($_value_)) {
        if ($k !== $i) {
          return ObjectType::HASH;
        }
        $i++;
      }
      return ObjectType::REAL_ARRAY;
    }
    elseif (\is_object($_value_)) {
      return ObjectType::OBJECT;
    }
    elseif (\is_resource($_value_)) {
      return ObjectType::RESOURCE;
    }
    else {
      return ObjectType::UNKNOWN;
    }
  }

  static function IsComplex($_value_) {
    return !self::IsSimple($_value_);
  }

  static function IsSimple($_value_) {
    switch ($type = self::GetType($_value_)) {
    case ObjectType::BOOLEAN:
    case ObjectType::INTEGER:
    case ObjectType::FLOAT:
    case ObjectType::STRING:
      return \TRUE;
    default:
      return \FALSE;
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ DynaLoader

final class DynaLoader {
  const FILE_EXTENSION = '.php';

  /// \brief Dynamically load a code file.
  /// WARNING: only works if the included file does not return FALSE.
  /// \throw InvalidOperationException
  static function LoadFile($_path_) {
    if (\FALSE === (include_once $_path_)) {
      throw new FileNotFoundRuntimeException(
        \sprintf('Unable to include the file: "%s".', $_path_));
    }
  }

  static function LoadBundle($_namespace_) {
    self::LoadFile(self::_ToPath($_namespace_ . 'Bundle'));
  }

  static function LoadType(TypeName $_typeName_) {
    self::LoadFile(self::_GetTypePath($_typeName_));
  }

  private static function _GetTypePath(TypeName $_typeName_) {
    return self::_ToPath($_typeName_->getQualifiedName());
  }

  private static function _ToPath($_name_) {
    return \str_replace(TypeName::DELIMITER, \DIRECTORY_SEPARATOR, $_name_) . self::FILE_EXTENSION;
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ Guard

final class Guard {
  static function NotEmpty($_value_, $_paramName_) {
    if (empty($_value_)) {
      throw new ArgumentNullException($_paramName_, 'Value can not be empty.');
    }
  }

  static function NotNull($_value_, $_paramName_) {
    if (\NULL === $_value_) {
      throw new ArgumentNullException($_paramName_, 'Value can not be null.');
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ ReadOnlyDictionary

// TODO: Not really ReadOnly, since the derived class can access the private property.
trait ReadOnlyDictionary {
  private $_store = array();

  /// \return boolean
  function has($_key_) {
    return \array_key_exists($_key_, $this->_store);
  }

  /// \return mixed
  function get($_key_) {
    $this->_checkKey($_key_);
    return $this->_store[$_key_];
  }

  private function _checkKey($_key_) {
    if (!$this->has($_key_)) {
      throw new KeyNotFoundException(\sprintf('The key "%s" does not exist.', $_key_));
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ Dictionary

trait Dictionary {
  use ReadOnlyDictionary;

  function set($_key_, $_value_) {
    $this->_store[$_key_] = $_value_;
  }

  function remove($_key_) {
    $this->_checkKey($_key_);
    unset($this->_store[$_key_]);
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Singleton pattern.
// #################################################################################################

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

// Borg pattern.
// #################################################################################################

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

// Observer pattern.
// #################################################################################################

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

// Provider.
// #################################################################################################

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
      $refl = new \ReflectionClass($providerClass);
      return $refl->newInstance($params);
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Configuration.
// #################################################################################################

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

// Miscs.
// #################################################################################################

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

// Diagnostics.
// #################################################################################################

// {{{ Broken

// }}} ---------------------------------------------------------------------------------------------

// DI container.
// #################################################################################################

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

// Caching.
// #################################################################################################

// {{{ Cache

interface Cache {
  /// \brief Return TRUE if cache exists, FALSE otherwise
  /// \param $_id_ (string) Cache Id
  /// \param $_namespace_ (string) Cache namespace
  /// \param $_test_validity_ (boolean) Check the cache validity
  /// \return (boolean) TRUE if cache exists, FALSE otherwise
  function has($_id_, $_namespace_, $_test_ = \TRUE);

  /// \brief Return cached data on success, NULL if no available cache
  /// \param $_id_ (string) Cache Id
  /// \param $_namespace_ (string) Cache namespace
  /// \param $_test_validity_ (boolean) Check the cache validity
  /// \return (mixed)
  function get($_id_, $_namespace_, $_test_ = \TRUE);

  /// \brief Put $_data_ into the cache
  /// \param $_id_ (string) Cache Id
  /// \param $_namespace_ (string) Cache namespace
  /// \param $_data_ (string) Data to be cached
  /// \return (boolean) TRUE on success, FALSE otherwise
  function put($_id_, $_namespace_, $_data_);

  /// \brief Delete cache
  /// \param $_id_ (string) Cache Id
  /// \param $_namespace_ (string) Cache namespace
  /// \return (boolean) TRUE on success, FALSE otherwise
  function remove($_id_, $_namespace_);

  /// \brief Get last modified time for cache
  /// \param $_id_ (string) Cache Id
  /// \param $_namespace_ (string) Cache namespace
  /// \return (string) Last modified time
  function getLastModified($_id_, $_namespace_);
}

// }}} ---------------------------------------------------------------------------------------------

// Persistence.
// #################################################################################################

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

// EOF
