<?php

namespace RESTafy;

require_once 'NarvaloBundle.php';

//const VERSION = '%%VERSION%%';

// Core classes
// =================================================================================================

const HTTP_EOL = "\n";

// {{{ HttpVersion

final class HttpVersion {
  const
    V_1_0 = '1.0',
    V_1_1 = '1.1';
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ HttpVerb

final class HttpVerb {
  const
    GET    = 'GET',
    POST   = 'POST',
    PUT    = 'PUT',
    DELETE = 'DELETE',
    HEAD   = 'HEAD';
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ DebugLevel

final class DebugLevel {
  const
    NONE       = 0,
    JAVASCRIPT = 1,
    STYLESHEET = 2,
    RUNTIME    = 4,
    DATABASE   = 8;

  /// Enable full debug.
  static function All() {
    return
      self::DATABASE
      | self::JAVASCRIPT
      | self::RUNTIME
      | self::STYLESHEET;
  }

  /// Only debug the UI.
  static function UI() {
    return self::JAVASCRIPT | self::STYLESHEET;
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ HttpError

final class HttpError {
  const
    SEE_OTHER             = 303,
    BAD_REQUEST           = 400,
    UNAUTHORIZED          = 401,
    FORBIDDEN             = 403,
    NOT_FOUND             = 404,
    METHOD_NOT_ALLOWED    = 405,
    PRECONDITION_FAILED   = 412,
    INTERNAL_SERVER_ERROR = 500;

  static function SeeOther($_url_) {
    self::_Header(self::SEE_OTHER);
    \header('Location: ' . $_url_);
    exit();
  }

  static function BadRequest($_msg_ = '') {
    self::_Render(self::BAD_REQUEST, $_msg_);
  }

  static function Unauthorized($_msg_ = '') {
    self::_Render(self::UNAUTHORIZED, $_msg_);
  }

  static function Forbidden($_msg_ = '') {
    self::_Render(self::FORBIDDEN, $_msg_);
  }

  static function NotFound($_msg_ = '') {
    self::_Render(self::NOT_FOUND, $_msg_);
  }

  static function MethodNotAllowed($_msg_ = '') {
    self::_Render(self::METHOD_NOT_ALLOWED, $_msg_);
  }

  static function PreconditionFailed ($_msg_ = '') {
    self::_Render(self::PRECONDITION_FAILED, $_msg_);
  }

  static function InternalServerError($_msg_ = '') {
    self::_Render(self::INTERNAL_SERVER_ERROR, $_msg_);
  }

  private static function _Header($_status_) {
    $statusLine = '';
    switch ($_status_) {
    case self::SEE_OTHER:
      $statusLine = '303 See Other'; break;
    case self::BAD_REQUEST:
      $statusLine = '400 Bad Request'; break;
    case self::UNAUTHORIZED:
      $statusLine = '401 Unauthorized'; break;
    case self::FORBIDDEN:
      $statusLine = '403 Forbidden'; break;
    case self::NOT_FOUND:
      $statusLine = '404 Not Found'; break;
    case self::METHOD_NOT_ALLOWED:
      $statusLine = '405 Method Not Allowed'; break;
    case self::PRECONDITION_FAILED:
      $statusLine = '412 Precondition Failed'; break;
    case self::INTERNAL_SERVER_ERROR:
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

// }}} ---------------------------------------------------------------------------------------------

// Addressing
// =================================================================================================

// {{{ Addr

interface Addr {
  function getUrl();
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ Url

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

// }}} ---------------------------------------------------------------------------------------------

// Assets
// =================================================================================================

// {{{ AssetProvider

interface AssetProvider {
  /// \return string
  function getImageUrl($_relativePath_);

  /// \return string
  function getScriptUrl($_relativePath_);

  /// \return string
  function getStyleUrl($_relativePath_);
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ SimpleAssetProvider

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

// }}} ---------------------------------------------------------------------------------------------
// {{{ DefaultAssetProviderParams

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

// }}} ---------------------------------------------------------------------------------------------
// {{{ DefaultAssetProvider

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

// }}} ---------------------------------------------------------------------------------------------
// {{{ AssetManager

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

// }}} ---------------------------------------------------------------------------------------------

// HTML helpers
// =================================================================================================

// {{{ HtmlHelper

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

// }}} ---------------------------------------------------------------------------------------------
// {{{ AssetHelper

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

// }}} ---------------------------------------------------------------------------------------------

// Views
// =================================================================================================

// {{{ Broken

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

// }}} ---------------------------------------------------------------------------------------------

// Actions & Controllers
// =================================================================================================

// {{{ Broken

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
//        HttpError::NOT_FOUND, $ae->getMessage());
//    } catch (\Exception $e) {
//      $view = $this->createErrorView_(
//        HttpError::INTERNAL_SERVER_ERROR, $e->getMessage());
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

// }}}

// EOF
