<?php

namespace Narvalo\Test\Sets;

require_once 'NarvaloBundle.php';

use \Narvalo;
use \Narvalo\Test\Sets\Internal as _;

// Core classes
// =================================================================================================

// {{{ TestSetException

class TestSetException extends Narvalo\Exception { }

// }}} ---------------------------------------------------------------------------------------------

// {{{ TestSet

interface TestSet {
  function getName();

  function run();
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ TestFixture

interface TestFixture {
  function setup();
  function teardown();
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ TestSuite

/// A very simple xUnit-like test set.
class TestSuite implements TestSet, TestFixture {
  private static $_MethodNamesToExclude;
  private
    $_name,
    $_testMethods;

  final function __construct() {
    self::_Initialize();

    $rfl = new \ReflectionObject($this);

    $this->_name = $rfl->getName();

    // All public methods in a derived class are considered to be a unit test case.
    $this->_testMethods = \array_filter(
      $rfl->getMethods(\ReflectionMethod::IS_PUBLIC),
      function($_method_) {
        return !\array_key_exists($_method_->getName(), self::$_MethodNamesToExclude);
      });

    if (empty($this->_testMethods)) {
      throw new TestSetException(
        \sprintf(
          'No test case found in "%s". Your TestSuite MUST contain at least one public method.',
          $this->_name));
    }
  }

  final function getName() {
    return $this->_name;
  }

  static function AutoRun() {
    $me = new static();
    $me->run();
  }

  final function run() {
    $this->setup();

    foreach ($this->_testMethods as $method) {
      $method->invoke($this);
    }

    $this->teardown();
  }

  function setup() {
    ;
  }

  function teardown() {
    ;
  }

  private static function _Initialize() {
    if (NULL === self::$_MethodNamesToExclude) {
      $rfl = new \ReflectionClass(__CLASS__);

      // All methods in TestSuite are to be excluded.
      self::$_MethodNamesToExclude
        = \array_flip(\array_map(
          function($_method_) { return $_method_->getName(); },
          $rfl->getMethods()
        ));
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------

// File-based test set
// =================================================================================================

// {{{ FileTestSet

class FileTestSet implements TestSet {
  private
    $_name,
    $_path;

  function __construct($_path_, $_name_ = \NULL) {
    $this->_path = $_path_;
    $this->_name = $_name_ ?: $_path_;
  }

  function getName() {
    return $this->_name;
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
    parent::__construct(new _\FileExtensionRecursiveFilterIterator($_it_, $_file_ext_));
  }

  static function FromPath($_path, $_file_ext_) {
    $it = new \RecursiveDirectoryIterator(
      $_path,
      \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);

    return new self($it, $_file_ext_);
  }

  function current() {
    return new FileTestSet(parent::current()->getPathname());
  }
}

// }}} ---------------------------------------------------------------------------------------------

// #################################################################################################

namespace Narvalo\Test\Sets\Internal;

// {{{ FileExtensionRecursiveFilterIterator

class FileExtensionRecursiveFilterIterator extends \RecursiveFilterIterator {
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
