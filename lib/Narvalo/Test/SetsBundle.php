<?php

namespace Narvalo\Test\Sets;

require_once 'NarvaloBundle.php';

use \Narvalo;
use \Narvalo\Test\Sets\Internal as _;

// Core classes
// =================================================================================================

// {{{ TestSet

interface TestSet {
  function getName();

  function run();
}

// }}} ---------------------------------------------------------------------------------------------

// xUnit-like test set
// =================================================================================================

// {{{ TestSuite

class TestSuite implements TestSet {
  private static $_MethodNamesToExclude;
  private
    $_name,
    $_testMethods;

  final function __construct() {
    $this->_selfInspect();
  }

  final function getName() {
    return $this->_name;
  }

  final function run() {
    $this->setup();

    foreach ($this->_testMethods as $method) {
      $method->invoke($this);
    }

    $this->teardown();
  }

  // Fixtures.

  function setup() {
    ;
  }

  function teardown() {
    ;
  }

  private static function & _GetMethodNamesToExclude() {
    if (NULL === self::$_MethodNamesToExclude) {
      $rfl = new \ReflectionClass(__CLASS__);

      self::$_MethodNamesToExclude
        = \array_flip(\array_map(
          function($_method_) { return $_method_->getName(); },
          $rfl->getMethods()
        ));
    }

    return self::$_MethodNamesToExclude;
  }

  private function _selfInspect() {
    $rfl = new \ReflectionObject($this);

    $this->_name = $rfl->getName();

    $names_to_exclude =& self::_GetMethodNamesToExclude();

    $this->_testMethods = \array_filter(
      $rfl->getMethods(),
      function($_method_) use ($names_to_exclude) {
        return !\array_key_exists($_method_->getName(), $names_to_exclude);
      });
  }
}

// }}} ---------------------------------------------------------------------------------------------

// File-based test set
// =================================================================================================

// {{{ FileTestSet

class FileTestSet implements TestSet {
  private $_path;

  function __construct($_path_) {
    $this->_path = $_path_;
  }

  function getName() {
    return $this->_path;
  }

  function run() {
    Narvalo\DynaLoader::LoadFile($this->_path);
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ FileTestSetIterator

class FileTestSetIterator extends \IteratorIterator {
  function __construct(array $_paths_) {
    parent::__construct(new \ArrayIterator($_paths_));
  }

  function current() {
    return new FileTestSet(parent::current());
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ InDirectoryFileTestSetIterator

class InDirectoryFileTestSetIterator extends \RecursiveIteratorIterator {
  function __construct(\RecursiveDirectoryIterator $_it_, $_file_ext_) {
    parent::__construct(new _\RecursiveFileExtensionFilterIterator($_it_, $_file_ext_));
  }

  static function FromPath($_directory_, $_file_ext_) {
    $it  = new \RecursiveDirectoryIterator($_directory_, \FilesystemIterator::SKIP_DOTS);

    return new self($it, $_file_ext_);
  }

  function current() {
    return new FileTestSet(parent::current()->getPathname());
  }
}

// }}} ---------------------------------------------------------------------------------------------

// #################################################################################################

namespace Narvalo\Test\Sets\Internal;

// {{{ RecursiveFileExtensionFilterIterator

class RecursiveFileExtensionFilterIterator extends \RecursiveFilterIterator {
  private
    $_fileExt,
    $_fileExtLength;

  function __construct(\RecursiveDirectoryIterator $_it_, $_file_ext_) {
    parent::__construct($_it_);

    $this->_fileExt       = $_file_ext_;
    $this->_fileExtLength = \strlen($_file_ext_);
  }

  function accept() {
    $current = $this->getInnerIterator()->current();

    return $this->hasChildren() || !$current->isFile() || $this->_match($current->getFilename());
  }

  function getChildren() {
    return new self($this->getInnerIterator()->getChildren(), $this->_fileExt);
  }

  private function _match($_value_) {
    return \substr($_value_, - $this->_fileExtLength) === $this->_fileExt;
  }
}

// }}} ---------------------------------------------------------------------------------------------

// EOF
