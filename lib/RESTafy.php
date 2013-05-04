<?php

namespace RESTafy;

const VERSION = '0.1.0';

class Exception extends \Exception {
  function __construct($_message_ = '', \Exception $_innerException_ = \NULL) {
    parent::__construct($_message_, 0, $_innerException_);
  }
}

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

class ArgumentNullException extends ArgumentException { }

class InvalidOperationException extends Exception { }

class KeyNotFoundException extends Exception { }

final class ObjectType {
  const
    Unknown   = 0,
    // Simple types.
    Null      = 1,
    Boolean   = 2,
    Integer   = 3,
    Float     = 4,
    String    = 5,
    // Complex types.
    RealArray = 10,
    HashArray = 11,
    Object    = 12,
    Resource  = 13;
}

class TypeName {
  const
    Delimiter       = '\\',
    GlobalNamespace = '\\';

  private static
    // Cf. http://www.php.net/manual/fr/language.oop5.basic.php
    $_TypeNameRegex = "/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/",
    // TODO: check this.
    $_NamespaceNameRegex = "/^[a-zA-Z_\x7f-\xff][\\a-zA-Z0-9_\x7f-\xff]*[a-zA-Z0-9_\x7f-\xff]$/";

  private
    /// \var string
    $_name,
    /// \var string
    $_namespace;

  function __construct($_name_, $_namespace_ = self::GlobalNamespace) {
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
    return self::Delimiter . $this->getQualifiedName();
  }

  /// \return string.
  function getName() {
    return $this->_name;
  }

  /// \return string.
  function getQualifiedName() {
    return $this->_namespace . self::Delimiter . $this->_name;
  }
}

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
      return ObjectType::Null;
    }
    elseif (\is_string($_value_)) {
      return ObjectType::String;
    }
    elseif (\is_int($_value_)) {
      return ObjectType::Integer;
    }
    elseif (\is_float($_value_)) {
      return ObjectType::Float;
    }
    elseif (\is_bool($_value_)) {
      return ObjectType::Boolean;
    }
    elseif (\is_array($_value_)) {
      // Much faster alternative to the usual snippet:
      // array_keys($_value_) === range(0, count($_value_) - 1)
      // || empty($_value_)
      $i = 0;
      while (list($k, ) = each($_value_)) {
        if ($k !== $i) {
          return ObjectType::HashArray;
        }
        $i++;
      }
      return ObjectType::RealArray;
    }
    elseif (\is_object($_value_)) {
      return ObjectType::Object;
    }
    elseif (\is_resource($_value_)) {
      return ObjectType::Resource;
    }
    else {
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

final class DynaLoader {
  const FileExtension = '.php';

  /// \brief Dynamically load a code file.
  /// WARNING: only works if the included file does not return FALSE.
  /// \throw InvalidOperationException
  static function LoadFile($_path_) {
    if (\FALSE === (include_once $_path_)) {
      throw new InvalidOperationException(\sprintf('Unable to include the file: "%s".', $_path_));
    }
  }

  static function LoadType(TypeName $_typeName_) {
    self::LoadFile( self::_GetTypePath($_typeName_) );
  }

  private static function _GetTypePath(TypeName $_typeName_) {
    return self::_ToPath($_typeName_->getQualifiedName());
  }

  private static function _ToPath($_name_) {
    return \str_replace(TypeName::Delimiter, \DIRECTORY_SEPARATOR, $_name_) . self::FileExtension;
  }
}

final class Guard {
  static function NotNull($_value_, $_paramName_) {
    if (\NULL === $_value_) {
      throw new ArgumentNullException($_paramName_, 'Value can not be null.');
    }
  }
}

// TODO: not really ReadOnly, since the derived class can access the private property.
trait ReadOnlyDictionary {
  private $_store;

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

// Singleton ---------------------------------------------------------------------------------------

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

// Borg --------------------------------------------------------------------------------------------

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

// TODO: extends \ArrayObject.
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

// Observer --------------------------------------------------------------------

interface Observer {
  function update(Observable $_observable_);
}

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
    foreach ($this->_observers as $observer) {
      $observer->update($this);
    }
  }
}

// Provider ----------------------------------------------------------------------

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

// Configuration ---------------------------------------------------------------

class ConfigurationException extends Exception { }

interface Configuration {
  function GetSection($_sectionName_);
}

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

// Miscs -----------------------------------------------------------------------

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

// Diagnostics -----------------------------------------------------------------

// Container -------------------------------------------------------------------

class ContainerException extends Exception { }

class ContainerBuilder {
  function build() {
    throw new NotImplementedException();
  }

  function register() {
    throw new NotImplementedException();
  }
}

class Container {
  function resolve() {
    throw new NotImplementedException();
  }
}

// Caching ---------------------------------------------------------------------

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

// Persistence -----------------------------------------------------------------

class DBIException extends Exception { }

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

// Diagnostics -----------------------------------------------------------------

const HTTP_EOL = "\n";

final class HttpVersion {
  const
    V_1_0 = '1.0',
    V_1_1 = '1.1';
}

final class HttpVerb {
  const
    Get    = 'GET',
    Post   = 'POST',
    Put    = 'PUT',
    Delete = 'DELETE',
    Head   = 'HEAD';
}

final class DebugLevel {
  const
    None       = 0,
    JavaScript = 1,
    StyleSheet = 2,
    RunTime    = 4,
    DataBase   = 8;

  /// Enable full debug.
  static function All() {
    return
      self::DataBase
      | self::JavaScript
      | self::RunTime
      | self::StyleSheet;
  }

  /// Only debug the UI.
  static function UI() {
    return self::JavaScript | self::StyleSheet;
  }
}

final class HttpError {
  const
    SeeOther            = 303,
    BadRequest          = 400,
    Unauthorized        = 401,
    Forbidden           = 403,
    NotFound            = 404,
    MethodNotAllowed    = 405,
    PreconditionFailed  = 412,
    InternalServerError = 500;

  static function SeeOther($_url_) {
    self::_Header(self::SeeOther);
    \header('Location: ' . $_url_);
    exit();
  }

  static function BadRequest($_msg_ = '') {
    self::_Render(self::BadRequest, $_msg_);
  }

  static function Unauthorized($_msg_ = '') {
    self::_Render(self::Unauthorized, $_msg_);
  }

  static function Forbidden($_msg_ = '') {
    self::_Render(self::Forbidden, $_msg_);
  }

  static function NotFound($_msg_ = '') {
    self::_Render(self::NotFound, $_msg_);
  }

  static function MethodNotAllowed($_msg_ = '') {
    self::_Render(self::MethodNotAllowed, $_msg_);
  }

  static function PreconditionFailed ($_msg_ = '') {
    self::_Render(self::PreconditionFailed, $_msg_);
  }

  static function InternalServerError($_msg_ = '') {
    self::_Render(self::InternalServerError, $_msg_);
  }

  private static function _Header($_status_) {
    $statusLine = '';
    switch ($_status_) {
    case self::SeeOther:
      $statusLine = '303 See Other'; break;
    case self::BadRequest:
      $statusLine = '400 Bad Request'; break;
    case self::Unauthorized:
      $statusLine = '401 Unauthorized'; break;
    case self::Forbidden:
      $statusLine = '403 Forbidden'; break;
    case self::NotFound:
      $statusLine = '404 Not Found'; break;
    case self::MethodNotAllowed:
      $statusLine = '405 Method Not Allowed'; break;
    case self::PreconditionFailed:
      $statusLine = '412 Precondition Failed'; break;
    case self::InternalServerError:
    default:
      $statusLine = '500 Internal Server Error'; break;
    }

    \header('HTTP/1.1 ' . $statusLine);
  }

  private static function _Render($_status_, $_msg_) {
    self::_Header($_status_);
    if ('' !== $_msg_) {
      echo $msg . HTTP_EOL;
    }
    exit();
  }
}

// Addr --------------------------------------------------------------------------

interface Addr {
  function getUrl();
}

final class Url {
  private
    $_baseUrl,
    $_relativePath;

  function __construct($_baseUrl_, $_relativePath_) {
    $this->_baseUrl      = $_baseUrl_;
    $this->_relativePath = $_relativePath_;
  }

  function __toString() {
    return $this->_baseUrl . $this->_relativePath_;
  }
}

// Assets ----------------------------------------------------------------------

interface AssetProvider {
  /// \return string
  function getImageUrl($_relativePath_);

  /// \return string
  function getScriptUrl($_relativePath_);

  /// \return string
  function getStyleUrl($_relativePath_);
}

class SimpleAssetProvider implements AssetProvider {
  function getImageUrl($_relativePath_) {
    return \sprintf('/assets/img/%s', $_relativePath_);
  }

  function getScriptUrl($_relativePath_) {
    return \sprintf('/assets/js/%s', $_relativePath_);
  }

  function getStyleUrl($_relativePath_) {
    return \sprintf('/assets/css/%s', $_relativePath_);
  }
}

class DefaultAssetProviderParams {
  private
    $_baseUrl,
    $_scriptVersion,
    $_styleVersion;

  function __construct($_baseUrl_, $_scriptVersion_, $_styleVersion_) {
    $this->_baseUrl = $_baseUrl_;
    $this->_scriptVersion = $_scriptVersion_;
    $this->_styleVersion = $_styleVersion_;
  }

  function getBaseUrl() {
    return $this->_baseUrl;
  }

  function getScriptVersion() {
    return $this->_scriptVersion;
  }

  function getStyleVersion() {
    return $this->_styleVersion;
  }
}

class DefaultAssetProvider implements AssetProvider {
  private $_params;

  function __construct($_params_) {
    $this->_params = $_params_;
  }

  function getImageUrl($_relativePath_) {
    return \sprintf('%s/img/%s', $this->_params->getBaseUrl(), $_relativePath_);
  }

  function getScriptUrl($_relativePath_) {
    return \sprintf(
      '%s/%s/js/%s',
      $this->_params->getBaseUrl(),
      $this->_params->getScriptVersion(),
      $_relativePath_);
  }

  function getStyleUrl($_relativePath_) {
    return \sprintf(
      '%s/%s/css/%s',
      $this->_params->getBaseUrl(),
      $this->_params->getStyleVersion(),
      $_relativePath_);
  }
}

final class AssetManager {
  private static $_Provider;

  static function GetImageUrl($_relativePath_) {
    return self::_GetProvider()->getImageUrl($_relativePath_);
  }

  static function GetScriptUrl($_relativePath_) {
    return self::_GetProvider()->getScriptUrl($_relativePath_);
  }

  static function GetStyleUrl($_relativePath_) {
    return self::_GetProvider()->getStyleUrl($_relativePath_);
  }

  private static function _GetProvider() {
    if (\NULL === self::$_Provider) {
      $section = ConfigurationManager::GetSection('AssetSection');

      self::$_Provider = ProviderHelper::InstantiateProvider($section);
    }
    return self::$_Provider;
  }
}

// HtmlHelper ------------------------------------------------------------------

final class HtmlHelper {
  static function SelfClosingTag($_name_, array $_attrs_ = array()) {
    return \sprintf('<%s%s/>', $_name_, self::SerializeAttrs($_attrs_));
  }

  static function Tag($_name_, $_inner_, array $_attrs_ = array()) {
    return \sprintf(
      '<%s%s>%s</%s>',
      $_name_,
      self::SerializeAttrs($_attrs_),
      $_inner_,
      $_name_);
  }

  static function SerializeAttrs(array $_attrs_) {
    $result = '';
    foreach ($_attrs_ as $k => $v) {
      $result .= \sprintf(' %s="%s"', $k, $v);
    }
    return $result;
  }

  static function ActionLink($_href_, $_inner_, array $_attrs_ = array()) {
    $_attrs_['href'] = $_href_;
    return self::Tag('a', $_inner_, $_attrs_);
  }
}

final class AssetHelper {
  static function Image($_path_, array $_attrs_ = array()) {
    $_attrs_['src'] = AssetManager::GetImageUrl($_path_);
    return HtmlHelper::SelfClosingTag('img', $_attrs_);
  }

  static function ImageLink($_path_, $_inner_, array $_attrs_ = array()) {
    return HtmlHelper::ActionLink(
      AssetManager::GetImageUrl($_path_), $_inner_, $_attrs_);
  }

  static function JavaScript($_path_, $_inline_ = '') {
    return HtmlHelper::Tag(
      'script',
      $_inline_,
      array('src' => AssetManager::GetScriptUrl($_path_)));
  }

  static function JavaScriptList(array $_paths_) {
    $tag = '';
    for ($i = 0, $count = \count($_paths_); $i < $count; $i++) {
      $tag .= self::JavaScript($_paths_[$i]);
    }
    return $tag;
  }

  static function Stylesheet($_path_, $_media_ = \NULL) {
    $attrs = array(
      'href' => AssetManager::GetStyleUrl($_path_),
      'rel'  => 'stylesheet'
    );
    if (\NULL !== $_media_) {
      $attrs['media'] = $_media_;
    }
    return HtmlHelper::SelfClosingTag('link', $attrs);
  }

  static function StylesheetList(array $_paths_, $_media_ = \NULL) {
    $tag = '';
    for ($i = 0, $count = \count($_paths_); $i < $count; $i++) {
      $tag .= self::Stylesheet($_paths_[$i], $_media_);
    }
    return $tag;
  }
}

// View ------------------------------------------------------------------------

//class ViewException extends Exception { }
//
//interface View {
//  function render();
//}
//
//class NoopView implements View {
//  function render() {
//    ;
//  }
//}
//
//abstract class AbstractView implements View {
//  protected
//    $data_ = array(),
//      $model_;
//
//  protected function __construct($_model_) {
//    $this->model_ = $_model_;
//  }
//
//  /// \return string
//  abstract function getViewPath();
//
//  /// \throw  ViewException
//  function render() {
//    // Extract the view's properties into current scope
//    $this->data_['Model'] = $this->model_;
//
//    \extract($this->data_, \EXTR_REFS);
//
//    if (\FALSE === (include $this->getViewPath())) {
//      throw new ViewException(
//        \sprintf('Unable to include the view: "%s".', $this->getViewPath()));
//    }
//  }
//}
//
//abstract class AbstractPage extends AbstractView {
//  protected function __construct($_model_) {
//    parent::__construct($_model_);
//  }
//
//  function render() {
//    // Clear existing buffers.
//    while (\ob_get_level() > 0) {
//      \ob_end_flush();
//    }
//    // Start buffering.
//    \ob_start();
//
//    try {
//      parent::render();
//    } catch (ViewException $e) {
//      // Fail with correct error code.
//      HttpError::InternalServerError();
//    }
//
//    // Output the result.
//    \ob_flush();
//    // End buffering.
//    \ob_end_flush();
//
//    exit();
//  }
//}
//
//abstract class AbstractMasterPage extends AbstractPage {
//  protected function __construct($_model_, AbstractChildView $_child_) {
//    parent::__construct($_model_);
//    $this->data_['Child'] = $_child_;
//  }
//}
//
//abstract class AbstractChildView extends AbstractView {
//  protected $master_;
//
//  protected function __construct($_master_, $_model_) {
//    $this->master_ = $_master_;
//
//    parent::__construct($_model_);
//  }
//
//  function render() {
//    $this->master_->render();
//  }
//
//  function renderChild() {
//    parent::render();
//  }
//}
//
//// Action & Controller ---------------------------------------------------------
//
//class ActionException extends Exception { }
//
//class ControllerException extends Exception { }
//
//final class ControllerContext {
//  private
//    $_actionName,
//    $_controllerName,
//    $_request;
//
//  function __construct($_controllerName_, $_actionName_, $_request_) {
//    $this->_controllerName = $_controllerName_;
//    $this->_actionName = $_actionName_;
//    $this->_request = $_request_;
//  }
//
//  function getActionName() {
//    return $this->_actionName;
//  }
//
//  function getControllerName() {
//    return $this->_controllerName;
//  }
//
//  function getRequest() {
//    return $this->_request;
//  }
//}
//
//interface ControllerFactory {
//  function createController($_controllerName_);
//}
//
//abstract class AbstractApplication {
//  const
//    ActionKey     = 'action',
//    ControllerKey = 'controller',
//    DefaultControllerName = 'home',
//    DefaultActionName     = 'index';
//
//  protected function __construct() {
//    ;
//  }
//
//  abstract function createController($_controllerName_);
//
//  abstract protected function createErrorView_($_status_, $_message_);
//
//  function processRequest(array &$_req_) {
//    $context = self::ResolveContext_($_req_);
//
//    try {
//      $view = $this->invokeAction_($context);
//    } catch (ActionException $ae) {
//      $view = $this->createErrorView_(
//        HttpError::NotFound, $ae->getMessage());
//    } catch (\Exception $e) {
//      $view = $this->createErrorView_(
//        HttpError::InternalServerError, $e->getMessage());
//    }
//
//    try {
//      $view->render();
//    } catch (ViewException $ve) {
//      HttpError::InternalServerError();
//    }
//  }
//
//  protected static function ResolveContext_(array &$_req_) {
//    $req = $_req_;
//
//    if (isset($_req_[self::ControllerKey])) {
//      $controllerName = $_req_[self::ControllerKey];
//      unset($req[self::ControllerKey]);
//    } else {
//      $controllerName = self::DefaultControllerName;
//    }
//
//    if (isset($_req_[self::ActionKey])) {
//      $actionName = $_req_[self::ActionKey];
//      unset($req[self::ActionKey]);
//    } else {
//      $actionName = self::DefaultActionName;
//    }
//
//    return new ControllerContext(
//      $controllerName,
//      $actionName,
//      $req
//    );
//  }
//
//  protected function invokeAction_(ControllerContext $_context_) {
//    $actionName     = $_context_->getActionName();
//    $controllerName = $_context_->getControllerName();
//    $request        = $_context_->getRequest();
//
//    $controller = $this->createController($controllerName);
//
//    try {
//      $refl = new \ReflectionObject($controller);
//
//      if (!$refl->hasMethod($actionName)) {
//        throw new ActionException('La page demandée n\'existe pas.');
//      }
//
//      return $refl->getMethod($actionName)->invoke($controller, $request);
//    } catch (\ReflectionException $e) {
//      throw new ActionException('La page demandée n\'existe pas.');
//    }
//  }
//}

// EOF
