<?php

namespace RESTafy;

require_once 'NarvaloBundle.php';

use \Narvalo;

// Assets
// =================================================================================================

interface IAssetProvider {
  function getImageUrl($_relativePath_);

  function getScriptUrl($_relativePath_);

  function getStyleUrl($_relativePath_);
}

class SimpleAssetProvider implements IAssetProvider {
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

class DefaultAssetProvider implements IAssetProvider {
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

  // Private methods
  // ---------------

  private static function _GetProvider() {
    if (\NULL === self::$_Provider) {
      $section = ConfigurationManager::GetSection('AssetSection');

      self::$_Provider = ProviderHelper::InstantiateProvider($section);
    }

    return self::$_Provider;
  }
}

// HTML helpers
// =================================================================================================

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

// EOF
