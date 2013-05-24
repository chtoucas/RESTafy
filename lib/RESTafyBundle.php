<?php

namespace RESTafy;

require_once 'NarvaloBundle.php';
require_once 'Narvalo\WebBundle.php';

use \Narvalo;
use \Narvalo\Web;

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
    $this->_baseUrl       = $_baseUrl_;
    $this->_scriptVersion = $_scriptVersion_;
    $this->_styleVersion  = $_styleVersion_;
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

// Instrumentation
// =================================================================================================

// {{{ HttpHandlerFactory

interface HttpHandlerFactory {
  function getHandler(HttpContext $_context_, $_requestType_, $_url_, $_pathTranslated_);
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ HttpModule

interface HttpModule {
  function init(HttpApplication $_context_);
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ HttpContext

final class HttpContext {
  private static $_Current;

  private
    $_errors = array(),
      $_request,
      $_response;

  function __construct(HttpRequest $_request_, HttpResponse $_response_) {
    $this->_request  = $_request_;
    $this->_response = $_response_;
  }

  function allErrors() {
    throw new Narvalo\NotImplementedException();
  }

  function application() {
    throw new Narvalo\NotImplementedException();
  }

  function error() {
    throw new Narvalo\NotImplementedException();
  }

  function getRequest() {
    return $this->_request;
  }

  function getResponse() {
    return $this->_response;
  }

  function addError() {
    throw new Narvalo\NotImplementedException();
  }

  function clearError() {
    throw new Narvalo\NotImplementedException();
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ HttpApplication

class HttpApplication implements HttpHandler {
  const
    BeginRequestEvent               = 1,
    AuthenticateRequestEvent        = 2,
    AuthorizeRequestEvent           = 3,
    PostAuthorizeRequestEvent       = 4,
    ResolveRequestCacheEvent        = 5,
    PostResolveRequestCacheEvent    = 6,
    PostMapRequestHandlerEvent      = 7,
    AcquireRequestStateEvent        = 8,
    PreRequestHandlerExecuteEvent   = 9,
    ReleaseRequestStateEvent        = 10,
    PostReleaseRequestStateEvent    = 11,
    UpdateRequestCacheEvent         = 12,
    PostUpdateRequestCacheEvent     = 13,
    LogRequestEvent                 = 14,
    PostLogRequestEvent             = 15,
    EndRequestEvent                 = 16,

    ErrorEvent                      = 20;

  private $_eventHandlers = array();

  function __construct() {
    ;
  }

  function getContext() {
    //return HttpContext::$Current;
  }

  function getRequest() {
    //return HttpContext::$Current->getRequest();
  }

  function getResponse() {
    //return HttpContext::$Current->getResponse();
  }

  function registerEventHandler($_eventType_, $_handler_) {
    // XXX: Use SplObjectStorage.
    if (!\array_key_exists($_eventType_, $this->_eventHandlers)) {
      $this->_eventHandlers[$_eventType_] = array();
    }

    $this->_eventHandlers[$_eventType_][] = $_handler_;
  }

  function onStart() {
    ;
  }

  function onEnd() {
    ;
  }

  function onError() {
    ;
  }

  function onSessionStart() {
    ;
  }

  function onSessionEnd() {
    ;
  }

  private function _processEvent($_eventType_) {
    foreach ($this->_eventHandlers[$_eventType_] as $handler) {
      $handler($this->getContext());
    }
  }

  function processRequest(HttpContext $_context_) {
    //HttpContext::$Current = $_context_;

    foreach ($this->_eventHandlers as $type => $handlers) {
      $this->_processEvent($type);
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ HttpServer

class HttpServer {
  private static $_Modules = array();
  private $_httpApplication;

  function __construct(HttpApplication $_httpApplication_) {
    $this->_httpApplication = $_httpApplication_;
  }

  static function AddModule(HttpModule $_module_) {
    self::$_Modules[] = $_module_;
  }

  function start() {
    $this->_httpApplication->onStart();

    foreach (self::$_Modules as $module) {
      $module->init($this->_httpApplication);
    }
  }

  function processRequest() {
    $context = new HttpContext(new HttpRequest(), new HttpResponse());
    $this->_httpApplication->processRequest($context);
  }

  function shutdown() {
    $this->_httpApplication->onEnd();
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Views
// =================================================================================================

// {{{ ViewException

class ViewException extends Exception { }

// }}} ---------------------------------------------------------------------------------------------

// Actions & Controllers
// =================================================================================================

// {{{ ActionException

class ActionException extends Exception { }

// }}} ---------------------------------------------------------------------------------------------
// {{{ ControllerException

class ControllerException extends Exception { }

// }}} ---------------------------------------------------------------------------------------------

// {{{ Broken

//// {{{ Url
//
//class Url {
//  protected
//    $scheme,
//    $host,
//    $user,
//    $pass,
//    $port,
//    $path,
//    $query,
//    $fragment;
//
//  function __construct($_scheme_, $_host_, array $_parts_ = array()) {
//    $this->setScheme($_scheme_);
//    $this->setHost($_host_);
//
//    $parts = $_parts_ + array(
//      'user'  => '',
//      'pass'  => '',
//      'port'  => '',
//      'path'  => '/',
//      'query' => '',
//      'fragment'  => ''
//    );
//
//    $this->user     = $parts['user'];
//    $this->pass     = $parts['pass'];
//    $this->port     = $parts['port'];
//    $this->path     = $parts['path'];
//    $this->query    = $parts['query'];
//    $this->fragment = $parts['fragment'];
//  }
//
//  function __toString() {
//    return $this->scheme . '://'
//      . $this->getAuthority()
//      . $this->path                                       // Path
//      . (($q = $this->query)    !== '' ? "?$q" : '')      // Query
//      . (($f = $this->fragment) !== '' ? "#$f" : '')      // Fragment
//      ;
//  }
//
//  function setScheme($_scheme_) {
//    switch ($_scheme_) {
//    case 'http':    break;
//    case 'https':   break;
//    default:
//      throw new Exception("Unsupported scheme: $_scheme_");
//    }
//
//    $this->scheme = $_scheme_;
//  }
//
//  function setHost($_host_) {
//    $this->host = $_host_;
//  }
//
//  function getScheme() {
//    return $this->scheme;
//  }
//
//  function getHost() {
//    return $this->host;
//  }
//
//  function normalize() {
//
//  }
//
//  function getUserInfo() {
//    return ($this->user && $this->pass)
//      ? $this->user . ':' . $this->pass
//      : '';
//  }
//
//  function getAuthority() {
//    return (($u = $this->getUserInfo()) !== '' ? "$u@" : '') // User info
//      . $this->host                                       // Host
//      . (($p = $this->port) !== '' ? ":$p" : '');         // Port
//  }
//
//  static function build($_url_) {
//    // Beware, parse_url() does not validate the given URL
//    $parts = parse_url($_url_);
//
//    if (\FALSE === $parts) {
//
//      throw new Exception("Couldn't parse: $_url_");
//    }
//
//    // Mandatory parameters
//    if (isset($parts['scheme']) && isset($parts['host'])) {
//      $scheme = $parts['scheme'];
//      $host   = $parts['host'];
//    } else {
//
//      throw new Exception("No given scheme or host: $_url_");
//    }
//
//    // Optional parameters
//    if (isset($parts['user']) && isset($parts['pass'])) {
//      $parts['userinfo'] = $parts['user'] . ':' . $parts['pass'];
//    }
//
//    return new self($scheme, $host, $parts);
//  }
//
//  static function buildQuery($_pairs_) {
//    return \http_build_query($_pairs_);
//  }
//
//  static function getQueryPairs($_query_) {
//    $pairs = array();
//
//    parse_str($_query_, $pairs);
//
//    return $pairs;
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------

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
//
//// {{{ DebugLevel
//
//final class DebugLevel {
//  const
//    NONE       = 0x00,
//    JAVASCRIPT = 0x01,
//    STYLESHEET = 0x02,
//    RUNTIME    = 0x04,
//    DATABASE   = 0x08;
//
//  /// Enable full debug.
//  static function All() {
//    return
//      self::DATABASE
//      | self::JAVASCRIPT
//      | self::RUNTIME
//      | self::STYLESHEET;
//  }
//
//  /// Only debug the UI.
//  static function UI() {
//    return self::JAVASCRIPT | self::STYLESHEET;
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ HttpError
//
//final class HttpError {
//  const
//    SEE_OTHER             = 303,
//    BAD_REQUEST           = 400,
//    UNAUTHORIZED          = 401,
//    FORBIDDEN             = 403,
//    NOT_FOUND             = 404,
//    METHOD_NOT_ALLOWED    = 405,
//    PRECONDITION_FAILED   = 412,
//    INTERNAL_SERVER_ERROR = 500;
//
//  static function SeeOther($_url_) {
//    self::_Header(self::SEE_OTHER);
//    \header('Location: ' . $_url_);
//    exit();
//  }
//
//  static function BadRequest($_msg_ = '') {
//    self::_Render(self::BAD_REQUEST, $_msg_);
//  }
//
//  static function Unauthorized($_msg_ = '') {
//    self::_Render(self::UNAUTHORIZED, $_msg_);
//  }
//
//  static function Forbidden($_msg_ = '') {
//    self::_Render(self::FORBIDDEN, $_msg_);
//  }
//
//  static function NotFound($_msg_ = '') {
//    self::_Render(self::NOT_FOUND, $_msg_);
//  }
//
//  static function MethodNotAllowed($_msg_ = '') {
//    self::_Render(self::METHOD_NOT_ALLOWED, $_msg_);
//  }
//
//  static function PreconditionFailed ($_msg_ = '') {
//    self::_Render(self::PRECONDITION_FAILED, $_msg_);
//  }
//
//  static function InternalServerError($_msg_ = '') {
//    self::_Render(self::INTERNAL_SERVER_ERROR, $_msg_);
//  }
//
//  private static function _Header($_status_) {
//    $statusLine = '';
//    switch ($_status_) {
//    case self::SEE_OTHER:
//      $statusLine = '303 See Other'; break;
//    case self::BAD_REQUEST:
//      $statusLine = '400 Bad Request'; break;
//    case self::UNAUTHORIZED:
//      $statusLine = '401 Unauthorized'; break;
//    case self::FORBIDDEN:
//      $statusLine = '403 Forbidden'; break;
//    case self::NOT_FOUND:
//      $statusLine = '404 Not Found'; break;
//    case self::METHOD_NOT_ALLOWED:
//      $statusLine = '405 Method Not Allowed'; break;
//    case self::PRECONDITION_FAILED:
//      $statusLine = '412 Precondition Failed'; break;
//    case self::INTERNAL_SERVER_ERROR:
//    default:
//      $statusLine = '500 Internal Server Error'; break;
//    }
//
//    \header('HTTP/1.1 ' . $statusLine);
//  }
//
//  private static function _Render($_status_, $_msg_) {
//    self::_Header($_status_);
//    if ('' !== $_msg_) {
//      echo $msg . HTTP_EOL;
//    }
//    exit();
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------

//// {{{ RouteConstraint
//
///// \brief  Définit le contrat que doit implémenter une classe pour vérifier si
/////         une valeur de paramètre d'URL est valide pour une contrainte.
//interface RouteConstraint {
//  /// \brief  Détermine si le paramètre d'URL contient une valeur valide pour cette contrainte.
//  /// \param  HttpContextBase $_httpContext_
//  ///         Objet qui encapsule des informations sur la requête HTTP.
//  /// \param  Route $_route_
//  ///         Objet auquel cette contrainte appartient.
//  /// \param  string $_parameterName_
//  ///         Nom du paramètre en cours de vérification.
//  /// \param  RouteValueDictionary $_values_
//  ///         Objet qui contient les paramètres de l'URL.
//  /// \param  RouteDirection $_routeDirection_
//  ///         Objet qui indique si le contrôle de contrainte est exécuté
//  //          lorsqu'une requête entrante est traitée ou lorsqu'une URL est générée.
//  /// \return bool. \TRUE si le paramètre d'URL contient une valeur valide ; sinon, \FALSE.
//  function match(HttpContextBase $_httpContext_, Route $_route_, $_parameterName_,
//    RouteValueDictionary $_values_, RouteDirection $_routeDirection_);
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ RouteHandler
//
///// \brief  Définit le contrat pour lequel une classe doit implémenter afin de
/////         traiter une requête pour un modèle d'itinéraire correspondant.
//interface RouteHandler {
//  /// \brief  Fournit l'objet qui traite la requête.
//  /// \param  RequestContext $_requestContext_
//  ///         Objet qui encapsule des informations sur la requête.
//  /// \return HttpHandler. Objet qui traite la requête.
//  function getHttpHandler(RequestContext $_requestContext_);
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//
//// {{{ RouteDirection
//
//final class RouteDirection {
//  const
//    IncomingRequest = 1,
//    UrlGeneration = 2;
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//
//// {{{ HttpMethodConstraint
//
///// \brief  Vous permet de définir quels verbes HTTP sont autorisés lorsque le
/////         routage ASP.NET détermine si une URL correspond à un itinéraire.
//class HttpMethodConstraint implements RouteConstraint {
//  private $_allowedMethods;
//
//  /// \brief  Initialise une nouvelle instance de la classe
//  ///         HttpMethodConstraint en utilisant les verbes HTTP
//  ///         autorisés pour l'itinéraire.
//  function __construct(array $_allowedMethods_) {
//    $this->_allowedMethods = $_allowedMethods_;
//  }
//
//  function allowedMethods() {
//    return $this->_allowedMethods;
//  }
//
//  /// \return Lorsque le routage ASP.NET traite une requête, true si la
//  ///         requête a été effectuée en utilisant un verbe HTTP autorisé ; sinon,
//  ///         false.Lorsque le routage ASP.NET construit une URL, true si les valeurs
//  ///         fournies contiennent un verbe HTTP qui correspond à l'un des verbes HTTP
//  ///         autorisés ; sinon, false. La valeur par défaut est true.
//  function match(HttpContextBase $_httpContext_, Route $_route_, $_parameterName_,
//    RouteValueDictionary $_values_, RouteDirection $_routeDirection_) {
//
//      throw new Narvalo\NotImplementedException();
//    }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ PageRouteHandler
//
///// \brief  Fournit des propriétés et des méthodes pour définir le mappage d'une
/////         URL à un fichier physique.
//class PageRouteHandler implements RouteHandler {
//  private
//    $_virtualPath,
//    $_checkPhysicalUrlAccess;
//
//  function __construct($_virtualPath_, $_checkPhysicalUrlAccess_ = \TRUE) {
//    $this->_virtualPath = $_virtualPath_;
//    $this->_checkPhysicalUrlAccess = $_checkPhysicalUrlAccess_;
//  }
//
//  function virtualPath() {
//    return $this->_virtualPath;
//  }
//
//  function checkPhysicalUrlAccess() {
//    return $this->_checkPhysicalUrlAccess;
//  }
//
//  function getSubstitutedVirtualPath(RequestContext $_requestContext_) {
//    throw new Narvalo\NotImplementedException();
//  }
//
//  /// \see RouteHandler::GetHttpHandler()
//  function getHttpHandler(RequestContext $_requestContext_) {
//    throw new Narvalo\NotImplementedException();
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ RequestContext
//
//class RequestContext {
//  private
//    /// \var HttpContextBase
//    $_httpContext,
//    /// \var RouteData
//    $_routeData;
//
//  function __construct(HttpContextBase $_httpContext_, RouteData $_routeData_) {
//    $this->_httpContext = $_httpContext_;
//    $this->_routeData = $_routeData_;
//  }
//
//  /// \brief  Informations sur la requête courante.
//  function httpContext() {
//    return $this->_httpContext;
//  }
//
//  /// \brief  Informations sur la route qui correspond à la requête courante.
//  function routeData() {
//    return $this->_routeData;
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//
//// {{{ RouteBase
//
//abstract class RouteBase {
//  protected function __construct() {
//    ;
//  }
//
//  /// \return RouteData
//  abstract function getRouteData(HttpContextBase $_httpContext_);
//
//  /// \return VirtualPathData
//  abstract function getVirtualPath(RequestContext $_requestContext_,
//    RouteValueDictionary $_values_);
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ Route
//
//class Route extends RouteBase {
//  private
//    $_constraints,
//    $_dataTokens,
//    $_defaults,
//    $_routeHandler,
//    $_url;
//
//  /// \param  string $_url_
//  ///         Motif d'URL pour la route
//  /// \param  RouteHandler $_routeHandler_
//  /// \param  RouteValueDictionary $_defaults_
//  ///         Si l'URL ne contient pas toutes les informations afin de
//  ///         compléter les paramètres de l'URL.
//  /// \param  RouteValueDictionary $_constraints_
//  /// \param  RouteValueDictionary $_dataTokens_
//  function __construct($_url_, RouteHandler $_routeHandler_,
//    RouteValueDictionary $_defaults_ = \NULL,
//    RouteValueDictionary $_constraints_ = \NULL,
//    RouteValueDictionary $_dataTokens_ = \NULL) {
//
//      $this->_url          = $_url_;
//      $this->_routeHandler = $_routeHandler_;
//      $this->_defaults     = $_defaults_    ?: new RouteValueDictionary();
//      $this->_constraints  = $_constraints_ ?: new RouteValueDictionary();
//      $this->_dataTokens   = $_dataTokens_  ?: new RouteValueDictionary();
//    }
//
//  function constraints() {
//    return $this->_constraints;
//  }
//
//  function dataTokens() {
//    return $this->_dataTokens;
//  }
//
//  function defaults() {
//    return $this->_defaults;
//  }
//
//  function routeHandler() {
//    return $this->_routeHandler;
//  }
//
//  function url() {
//    return $this->_url;
//  }
//
//  function processConstraint(HttpContextBase $_httpContext_,
//    $_constraint_, $_parameterName_, RouteValueDictionary $_values_,
//    RouteDirection $_routeDirection_) {
//
//      throw new Narvalo\NotImplementedException();
//    }
//
//  function getRouteData(HttpContextBase $_httpContext_) {
//    return new RouteData($this, $this->_routeHandler);
//  }
//
//  function getVirtualPath(RequestContext $_requestContext_,
//    RouteValueDictionary $_values_) {
//
//      throw new Narvalo\NotImplementedException();
//    }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//
//// {{{ RouteCollection
//
//class RouteCollection {
//  public
//    $routeExistingFiles = \FALSE;
//
//  protected
//    $items = array();
//
//  private
//    $_count = 0;
//
//  function __construct() {
//    ;
//  }
//
//  function count() {
//    return count($this->items);
//  }
//
//  function items() {
//    return $this->items;
//  }
//
//  function add(RouteBase $_item_) {
//    $this->items[] = $_item_;
//  }
//
//  function clear() {
//    $this->items = array();
//  }
//
//  function ignore($_url_, $_constraints_ = \NULL) {
//    ;
//  }
//
//  function insert($_index_, RouteBase $_item_) {
//    if ($_index_ < 0 || $_index_ > $this->Count()) {
//      throw new ArgumentOutOfRangeException();
//    }
//    $this->_items[$_index_] = $_item_;
//  }
//
//  function getItem($_index_) {
//    if ($_index_ < 0 || $_index_ >= $this->count()) {
//      throw new Narvalo\ArgumentOutOfRangeException();
//    }
//    return $this->items[$_index_];
//  }
//
//  /// \return RouteBase
//  function getItemByName($_name_) {
//    ;
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ RouteData
//
//class RouteData {
//  private
//    $_route,
//    $_routeHandler;
//
//  function __construct(RouteBase $_route_, RouteHandler $_routeHandler_) {
//    $this->_route = $_route_;
//    $this->_routeHandler = $_routeHandler_;
//  }
//
//  /// \brief  Gets a collection of custom values that are passed to the route handler
//  ///         but are not used when ASP.NET routing determines whether the route matches a request.
//  /// \return RouteValueDictionary
//  function dataTokens() {
//    return $this->_route->DataTokens();
//  }
//
//  /// \brief  Gets the object that represents a route.
//  /// \return RouteBase
//  function route() {
//    return $this->_route;
//  }
//
//  /// \brief  Gets the object that processes a requested route.
//  /// \return RouteHandler
//  function routeHandler() {
//    return $this->_routeHandler;
//  }
//
//  /// \brief  Gets a collection of URL parameter values and default values for the route.
//  /// \return RouteValueDictionary
//  function values() {
//    throw new Narvalo\NotImplementedException();
//  }
//
//  /// \brief  Retrieves the value with the specified identifier.
//  /// \throw  InvalidOperationException
//  /// \return string. The element in the Values property whose key matches $_valueName_.
//  function getRequiredString($_valueName_) {
//    $result = \NULL;
//    $this->_route->Defaults()->TryGetValue($_valueName_, $result);
//    return $result;
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ RouteTable
//
//class RouteTable {
//  private static
//    /// \var RouteTable
//    $_Instance = \NULL;
//
//  protected
//    /// \var RouteCollection
//    $routes;
//
//  function __construct() {
//    $this->routes = new RouteCollection();
//  }
//
//  /// \return RouteCollection
//  static function Routes() {
//    return self::_Instance()->routes;
//  }
//
//  private static function _Instance() {
//    if (NULL === self::$_Instance) {
//      self::$_Instance = new static();
//    }
//    return self::$_Instance;
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ RouteValueDictionary
//
//class RouteValueDictionary {
//  private $_hash = array();
//
//  function __construct(array $_dictionary_ = \NULL) {
//    $this->_hash = $_dictionary_ ?: array();
//  }
//
//  function count() {
//    return count($this->_hash);
//  }
//
//  function keys() {
//    return array_keys($this->_hash);
//  }
//
//  function values() {
//    return array_values($this->_hash);
//  }
//
//  function add($_key_, $_value_) {
//    $this->_hash[$_key_] = $_value_;
//  }
//
//  /// \return bool.
//  function tryGetValue($_key_, &$_value_) {
//    if (array_key_exists($_key_, $this->_hash)) {
//      $_value_ = $this->_hash[$_key_];
//      return \TRUE;
//    }
//    else {
//      return \FALSE;
//    }
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ StopRoutingHandler
//
//class StopRoutingHandler implements RouteHandler {
//  function getHttpHandler(RequestContext $_requestContext_) {
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ UrlRoutingHandler
//
//class UrlRoutingHandler implements HttpHandler {
//  function processRequest(HttpContext $_context_) {
//    ;
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ UrlRoutingModule
//
//class UrlRoutingModule implements HttpModule {
//  public
//    /// \var RouteCollection
//    $routeCollection;
//
//  function __construct() {
//    ;
//  }
//
//  function init(HttpApplication $_context_) {
//    $this->routeCollection = RouteTable::Routes();
//
//    $_context_->registerEventHandler(
//      HttpApplication::ResolveRequestCacheEvent, self::Handler($this));
//  }
//
//  static function Handler(UrlRoutingModule $_mod_) {
//    return function(HttpContext $_httpContext_) use (&$_mod_) {
//      echo "In UrlRoutingModule\n";
//      // si la requête correspond à un fichier physique
//
//      // sinon on regarde dans la table de routage
//
//      // si la requêtee correspond à une route, on récupère le
//      // RouteHandler correspondant puis le HttpHandler
//      $route = $_mod_->routeCollection->getItem(0);
//
//      $requestContext
//        = new RequestContext($_httpContext_, $route->getRouteData($_httpContext_));
//
//      $route->routeHandler()
//        ->getHttpHandler($requestContext)->processRequest($_httpContext_);
//    };
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ VirtualPathData
//
//class VirtualPathData {
//  private
//    $_route,
//    $_virtualPath;
//
//  function __construct(RouteBase $_route_, $_virtualPath_) {
//    $this->_route = $_route_;
//    $this->_virtualPath = $_virtualPath_;
//  }
//
//  function dataTokens() {
//    throw new Narvalo\NotImplementedException();
//  }
//
//  function route() {
//    return $this->_route;
//  }
//
//  function virtualPath() {
//    return $this->_virtualPath;
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//
//// MVC
//
//// {{{ MvcHandler
//
//class MvcHandler implements HttpHandler {
//  static
//    /// \var bool
//    $DisableMvcResponseHeader = \FALSE;
//
//  private static
//    /// \var string
//    $_MvcVersion = VERSION,
//    /// \var string
//    $_MvcVersionHeaderName = 'X-Narvalo-Version';
//
//  private
//    /// \var ControllerBuilder
//    $_controllerBuilder,
//    /// \var RequestContext
//    $_requestContext;
//
//  function __construct(RequestContext $_requestContext_) {
//    $this->_requestContext = $_requestContext_;
//  }
//
//  function controllerBuilder() {
//    if (NULL === $this->_controllerBuilder) {
//      $this->_controllerBuilder = ControllerBuilder::Current();
//    }
//
//    return $this->_controllerBuilder;
//  }
//
//  function setControllerBuilder(ControllerBuilder $_controllerBuilder_) {
//    $this->_controllerBuilder = $_controllerBuilder_;
//  }
//
//  function requestContext() {
//    return $this->_requestContext;
//  }
//
//  protected function addVersionHeader(HttpContextBase $_httpContext_) {
//    if (!self::$DisableMvcResponseHeader) {
//      $_httpContext_->response()
//        ->appendHeader(self::$_MvcVersionHeaderName, self::$_MvcVersion);
//    }
//  }
//
//  function processRequest(HttpContext $_httpContext_) {
//    $this->addVersionHeader($_httpContext_);
//    $this->_removeOptionalRoutingParameters();
//
//    // récupère le type de contrôleur
//    $controllerName = $this->_requestContext->routeData()->getRequiredString('controller');
//
//    // initialise puis execute le contrôleur
//    $factory = $this->controllerBuilder()->controllerFactory;
//    $controller = $factory->createController($this->_requestContext, $controllerName);
//
//    if (NULL === $controller) {
//      throw new System\InvalidOperationException();
//    }
//
//    $controller->execute($this->_requestContext);
//  }
//
//  private function _removeOptionalRoutingParameters() {
//    //$rvd = $this->_requestContext->RouteData()->Values();
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ MvcRouteHandler
//
//class MvcRouteHandler implements IRouteHandler {
//  function getHttpHandler(RequestContext $_requestContext_) {
//    return new MvcHandler($_requestContext_);
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//
//// {{{ ActionInvoker
//
//interface ActionInvoker {
//  function invokeAction(ControllerContext $_controllerContext_, $_actionName_);
//}
//
//// }}}
//// {{{ CodeManager
//
//interface CodeManager {
//  /// \return Tableau de Assembly
//  function getReferencedAssemblies();
//
//  function readCachedFile($_fileName_);
//
//  function createCachedFile($_fileName_);
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ Controller
//
//interface Controller {
//  function execute(RequestContext $_requestContext_);
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ ControllerFactory
//
//interface ControllerFactory {
//  /// \return Controller
//  function createController(RequestContext $_requestContext_, $_controllerName_);
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//
//// {{{ CodeManagerWrapper
//
//class CodeManagerWrapper implements CodeManager {
//  private
//    $_cacheDir;
//
//  function getReferencedAssemblies() {
//    return System\CodeManager::GetReferencedAssemblies();
//  }
//
//  function readCachedFile($_fileName_) {
//    throw new System\NotImplementedException();
//  }
//
//  function createCachedFile($_fileName_) {
//    throw new System\NotImplementedException();
//
//    $fileName = $this->_getCachedFilePath($_fileName_);
//    $stream = new \SplFileObject($fileName, 'w');
//    return $stream;
//  }
//
//  private function _getCachedFilePath($_fileName_) {
//    return $this->_cacheDir . \DIRECTORY_SEPARATOR . $_fileName_;
//  }
//}
//
//// }}}
//// {{{ TypeCacheSerializer
//
//class TypeCacheSerializer {
//  function deserializeTypes(\SplFileObject $_input_) {
//    throw new System\NotImplementedException();
//  }
//
//  function serializeTypes(array $_types_, \SplFileObject $_output_) {
//    // FIXME
//    foreach ($_types_ as $type) {
//      $_output_->fwrite($type->fullName() . PHP_EOL);
//    }
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ TypeCacheUtil
//
//class TypeCacheUtil {
//  /// \return Tableau de Type
//  static function getFilteredTypesFromAssemblies($_cacheName_, $_predicate_,
//    CodeManager $_codeManager_) {
//
//      $serializer = new TypeCacheSerializer();
//
//      // on essaye d'abord de lire le cache
//      $matchingTypes
//        = self::ReadTypesFromCache($_cacheName_, $_predicate_, $_codeManager_, $serializer);
//      if (NULL !== $matchingTypes) {
//        return $matchingTypes;
//      }
//
//      // si la lecture du cache a échoué, on cherche dans les espaces de nom demandés
//      $matchingTypes = self::FilterTypesInAssemblies($_codeManager_, $_predicate_);
//
//      // sauvegarde du cache
//      self::SaveTypesToCache($_cacheName_, $matchingTypes, $_codeManager_, $serializer);
//
//      return $matchingTypes;
//    }
//
//  /// \return Array of Type
//  protected static function ReadTypesFromCache($_cacheName_, $_predicate_,
//    CodeManager $_codeManager_, TypeCacheSerializer $_serializer_) {
//
//      return \NULL;
//      throw new System\NotImplementedException();
//    }
//
//  protected static function SaveTypesToCache($_cacheName_, array $_matchingTypes_,
//    CodeManager $_codeManager_, TypeCacheSerializer $_serializer_) {
//
//      try {
//        $stream = $_codeManager_->createCachedFile($_cacheName_);
//
//        if (NULL !== $stream) {
//          $_serializer_->serializeTypes($_matchingTypes_, $stream);
//        }
//      }
//      catch (\Exception $e) {
//        ;
//      }
//    }
//
//  /// \return Array of Type
//  protected static function FilterTypesInAssemblies(CodeManager $_codeManager_, $_predicate_) {
//    $types = array();
//
//    $assemblies = $_codeManager_->getReferencedAssemblies();
//
//    foreach ($assemblies as $assembly) {
//      $it = new \FilesystemIterator($assembly->AbsolutePath(),
//        \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS);
//
//      while ($it->valid()) {
//        if ($it->isDir()) {
//          $it->next();
//        }
//
//        $className = $it->current()->getBasename('.php');
//
//        if (!System\Type::IsValid($className)) {
//          $it->next();
//        }
//
//        $type = new System\Type($className, $assembly->Name());
//
//        $type->load();
//
//        if ($_predicate_($type)) {
//          $types[] = $type;
//        }
//
//        $it->next();
//      }
//    }
//
//    return $types;
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//
//// {{{ ControllerTypeCache
//
//class ControllerTypeCache {
//  const TYPE_CACHE_NAME = 'MVC-ControllerTypeCache.php';
//
//  private $_cache = \NULL;
//
//  function ensureInitialized(CodeManager $_codeManager_) {
//    if (NULL === $this->_cache) {
//      $controllerTypes = TypeCacheUtil::GetFilteredTypesFromAssemblies(
//        self::TYPE_CACHE_NAME, self::IsControllerType(), $_codeManager_);
//
//      foreach ($controllerTypes as $type) {
//        $this->_cache[ $type->fullName() ] = $type;
//      }
//    }
//  }
//
//  /// \return Array of Type
//  function getControllerTypes($_controllerName_, array $_namespaces_) {
//    $matchingTypes = array();
//
//    foreach ($_namespaces_ as $_requestedNamespace_) {
//      $fullName = System\Type::GetFullName(
//        $_controllerName_ . 'Controller', $_requestedNamespace_);
//
//      if (array_key_exists($fullName, $this->_cache)) {
//        $matchingTypes[] = $this->_cache[$fullName];
//      }
//    }
//
//    return $matchingTypes;
//  }
//
//  protected static function IsControllerType() {
//    return function(System\Type $_type_) {
//      $refl = new \ReflectionClass($_type_->FullName());
//
//      return 'Controller' === substr($_type_->Name(), strlen($_type_->Name()) - 10)
//        && $refl->isInstantiable()
//        && $refl->implementsInterface('RESTafy\Controller');
//    };
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ DefaultControllerFactory
//
//class DefaultControllerFactory implements ControllerFactory {
//  private
//    /// \var ControllerBuilder
//    $_builder,
//    /// \var CodeManager
//    $_codeManager,
//    /// \var ControllerTypeCache
//    $_controllerTypeCache;
//
//  function __construct(ControllerBuilder $_builder_) {
//    $this->_builder = $_builder_;
//  }
//
//  protected function controllerTypeCache() {
//    if (NULL === $this->_controllerTypeCache) {
//      $this->_controllerTypeCache = new ControllerTypeCache();
//    }
//    return $this->_controllerTypeCache;
//  }
//
//  protected function codeManager() {
//    if (NULL === $this->_codeManager) {
//      $this->_codeManager = new CodeManagerWrapper();
//    }
//    return $this->_codeManager;
//  }
//
//  function createController(
//    RequestContext $_requestContext_, $_controllerName_) {
//
//        /* if (NULL === $_requestContext_) { // XXX C# specific check?
//            throw new System\ArgumentNullException('requestContext');
//        } */
//
//      if (NULL === $_controllerName_) { // XXX Use empty?
//        throw new System\ArgumentException('controllerName');
//      }
//
//      $type = $this->getControllerType($_requestContext_, $_controllerName_);
//      $controller = $this->getControllerInstance($_requestContext_, $type);
//
//      return $controller;
//    }
//
//  /// \return Controller
//  protected function getControllerInstance(RequestContext $_requestContext_,
//    System\Type $_controllerType_) {
//
//      if (NULL === $_controllerType_) {
//        throw new HttpException(404,
//          'The controller for path XXX was not found or does not implement Controller.');
//      }
//
//      $_controllerType_->load();
//
//      $className = $_controllerType_->fullName();
//
//      // Check existence of className
//      if (!class_exists($className, \FALSE)) {
//        throw new System\InvalidOperationException();
//      }
//
//      $controller = new $className();
//      return $controller;
//    }
//
//  /// \return string
//  protected function getControllerType(RequestContext $_requestContext_,
//    $_controllerName_) {
//
//      // Search in the current route's namespace collection
//      $routeNamespaces = array();
//
//      $val = $_requestContext_->routeData()
//        ->dataTokens()->tryGetValue('Namespaces', $routeNamespaces);
//
//      if (TRUE === $val) {
//        $match = $this->getControllerTypeWithinNamespaces(
//          $_requestContext_->routeData()->route(),
//          $_controllerName_,
//          $routeNamespaces);
//
//        if (NULL !== $match) {
//          return $match;
//        }
//      }
//    }
//
//  /// \return string
//  private function getControllerTypeWithinNamespaces(RouteBase $_route_,
//    $_controllerName_, array $_namespaces_) {
//
//      $controllerTypeCache = $this->controllerTypeCache();
//      $controllerTypeCache->ensureInitialized($this->CodeManager());
//
//      $matchingTypes
//        = $controllerTypeCache->getControllerTypes(
//          $_controllerName_, $_namespaces_);
//
//      switch ($count = count($matchingTypes)) {
//      case 0:
//        // No matching controller found
//        return \NULL;
//      case 1:
//        // Exactly one controller found
//        return $matchingTypes[0];
//      case 2:
//        // Ambiguous controller
//        throw new System\InvalidOperationException();
//      }
//    }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ ControllerActionInvoker
//
//class ControllerActionInvoker implements ActionInvoker {
//  function __construct() {
//    ;
//  }
//
//  function invokeAction(ControllerContext $_controllerContext_, $_actionName_) {
//    if (empty($_actionName_)) {
//      throw new System\ArgumentException('actionName');
//    }
//
//    $controller = $_controllerContext_->controller();
//
//    try {
//      $refl = new \ReflectionObject($controller);
//
//      if (!$refl->hasMethod($_actionName_)) {
//        return \FALSE;
//      }
//
//      $refl->getMethod($_actionName_)->invoke($controller, \NULL);
//
//      return \TRUE;
//    }
//    catch (\ReflectionException $e) {
//      return \FALSE;
//    }
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ ControllerBuilder
//
//class ControllerBuilder {
//  private static $_Instance;
//
//  public
//    /// \var ControllerFactory
//    $controllerFactory;
//
//  private
//    /// \var Array of strings
//    $_namespaces = array();
//
//  function __construct() {
//    $this->controllerFactory = new DefaultControllerFactory($this);
//  }
//
//  static function Current() {
//    if (NULL === self::$_Instance) {
//      self::$_Instance = new self();
//    }
//
//    return self::$_Instance;
//  }
//
//  /// \return Array of strings
//  function defaultNamespaces() {
//    return $this->_namespaces;
//  }
//
//    /*
//    // {{{ getControllerFactory()
//
//    /// \return ControllerFactory
//    function getControllerFactory() {
//        return $this->_factory;
//    }
//
//    // }}}
//    // {{{ setControllerFactory()
//
//    /// \return void
//    function setControllerFactory(ControllerFactory $_controllerFactory_) {
//        $this->_factory = $_controllerFactory_;
//    }
//
//    // }}}
//     */
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ ControllerBase
//
//abstract class ControllerBase implements Controller {
//  public
//    $controllerContext,
//    $validateRequest = \TRUE;
//
//  private
//    $_tempDataDictionary,
//    $_viewDataDictionary;
//
//  protected function __construct() {
//    ;
//  }
//
//  abstract function executeCore();
//
//  function execute(RequestContext $_requestContext_) {
//    $this->Initialize($_requestContext_);
//    $this->executeCore();
//  }
//
//  function Initialize(RequestContext $_requestContext_) {
//    $this->controllerContext = new ControllerContext($_requestContext_, $this);
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ Controller
//
//class Controller extends ControllerBase {
//  private $_actionInvoker;
//
//  function __construct() {
//    ;
//  }
//
//  function getActionInvoker() {
//    if (NULL === $this->_actionInvoker) {
//      $this->_actionInvoker = $this->createActionInvoker();
//    }
//
//    return $this->_actionInvoker;
//  }
//
//  function setActionInvoker(ActionInvoker $_value_) {
//    $this->_actionInvoker = $_value_;
//  }
//
//  function executeCore() {
//    try {
//      $actionName = $this->controllerContext->routeData()->getRequiredString('action');
//
//      if (!$this->getActionInvoker()->invokeAction($this->controllerContext, $actionName)) {
//        $this->handleUnknownAction($actionName);
//      }
//    }
//    catch (\Exception $e) {
//      ;
//    }
//  }
//
//  protected function createActionInvoker() {
//    return new ControllerActionInvoker();
//  }
//
//  protected function handleUnknownAction($_actionName_) {
//    throw new HttpException(404, 'Unknown action');
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ ControllerContext
//
//class ControllerContext {
//  private
//    /// \var ControllerBase
//    $_controller,
//    /// \var HttpContextBase
//    $_httpContext,
//    /// \var RequestContext
//    $_requestContext,
//    /// \var RouteData
//    $_routeData;
//
//  function __construct(RequestContext $_requestContext_,
//    ControllerBase $_controller_) {
//
//      $this->_requestContext = $_requestContext_;
//      $this->_controller = $_controller_;
//    }
//
//  function controller() {
//    return $this->_controller;
//  }
//
//  function httpContext() {
//    if (NULL === $this->_httpContext) {
//      $this->_httpContext = $this->_requestContext->httpContext;
//    }
//
//    return $this->_httpContext;
//  }
//
//  function requestContext() {
//    return $this->_requestContext;
//  }
//
//  function routeData() {
//    if (NULL === $this->_routeData) {
//      $this->_routeData = $this->_requestContext->routeData();
//    }
//
//    return $this->_routeData;
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//
//// {{{ MvcRouteTable
//
//class MvcRouteTable extends RouteTable {
//  function __construct() {
//    $this->routes = new MvcRouteCollection();
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ MvcRouteCollection
//
//class MvcRouteCollection extends RouteCollection {
//  function ignoreRoute($_url_, $_constraints_) {
//    ;
//  }
//
//  function mapRoute($_url_,
//    RouteValueDictionary $_defaults_ = \NULL,
//    RouteValueDictionary $_constraints_ = \NULL,
//    array $_namespaces_ = \NULL) {
//
//      if (NULL === $_url_) {
//        throw new System\ArgumentNullException('url');
//      }
//
//      $route = new Route($_url_,
//        new MvcRouteHandler(), $_defaults_, $_constraints_);
//
//      if (NULL !== $_namespaces_ && !empty($_namespaces_)) {
//        $route->dataTokens()->add('Namespaces', $_namespaces_);
//      }
//
//      $this->add($route);
//
//      return $route;
//    }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//
//// {{{ View
//
//interface View {
//  function render(ViewContext $_viewContext_); //, TextWriter $_writer_);
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//// {{{ ViewEngine
//
//interface ViewEngine {
//  function findPartialView(ControllerContext $_controllerContext_, $_partialViewName_,
//    $_useCache_);
//
//  function findView(ControllerContext $_controllerContext_, $_viewName_,
//    $_masterName_, $_useCache_);
//
//  function releaseView(ControllerContext $_controllerContext_, View $_view_);
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//
//// {{{ ViewDataDictionary
//
//class ViewDataDictionary {
//
//}
//
//// }}}
//// {{{ ViewContext
//
//class ViewContext extends ControllerContext {
//  function __construct(ControllerContext $_controllerContext_, View $_view_) {
//    //, ViewDataDictionary $_viewData_, TempDataDictionary tempData, TextWriter writer)
//
//    parent::__construct($_controllerContext_->RequestContext(),
//      $_controllerContext_->Controller());
//
//    $this->_view = $_view_;
//    //$this->_viewData = $_viewData_;
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------
//
//// {{{ Page
//
//class Page {
//  static $Repository = '.';
//  private $_template;
//
//  function __construct($_template_) {
//    $this->_template = $_template_;
//  }
//
//  function render($_model_ = \NULL) {
//    // Extract the view's properties into current scope
//    if (NULL !== $_model_) {
//      extract($_model_, EXTR_REFS);
//    }
//
//    $tpl = self::$Repository . DIRECTORY_SEPARATOR . $this->_template;
//
//    if ((include $tpl) != 1) {
//      throw new Exception('Unable to find view');
//    }
//  }
//}
//
//// }}}
//// {{{ View
//
//class View {
//  private
//    $_repository = '.',
//    $_data = array();
//
//  function __construct($_repository_ = null) {
//    if (isset($_repository_)) {
//      $this->_repository = $_repository_;
//    }
//  }
//
//  function __set($_name_, $_value_) {
//    $this->_data[$_name_] = $_value_;
//  }
//
//  function __get($_name_) {
//    if (array_key_exists($name, $this->_data)) {
//      return $this->data[$name];
//    }
//    else {
//      return null;
//    }
//  }
//
//  function __isset($_name_) {
//    return isset($this->_data[$_name_]);
//  }
//
//  function __unset($_name_) {
//    unset($this->_data[$_name_]);
//  }
//
//  function render($_page_) {
//    // Extract the view's properties into current scope
//    extract($this->_data, EXTR_REFS);
//
//    $tpl = $this->_repository . DIRECTORY_SEPARATOR . $_page_;
//
//    if ((include $tpl) != 1) {
//      error_log('Unable to find view: ' . $tpl);
//
//      header('HTTP/1.0 404 Not Found');
//      header('Connection: close');
//      exit();
//    }
//  }
//}
//
//// }}} ---------------------------------------------------------------------------------------------

// }}}

// http://labs.blitzagency.com/?p=2494
// https://github.com/jim/fitzgerald/blob/master/lib/fitzgerald.php
// http://code.google.com/p/addendum/
// https://github.com/symfony/symfony-sandbox/blob/master/src/vendor/symfony/src/Symfony/Component/outing/Route.php

// EOF
