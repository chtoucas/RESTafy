<?php

namespace Narvalo\Test\Sets;

require_once 'NarvaloBundle.php';

use \Narvalo;
use \Narvalo\Test\Sets\Internal as _;

// Core classes
// =================================================================================================

class TestSetException extends Narvalo\Exception { }

interface ITestSet {
  function getName();

  function run();
}

interface ITestFixture {
  function setup();

  function teardown();
}

// A very simple xUnit-like test set.
class TestSuite implements ITestSet, ITestFixture {
  private static $_MethodNamesToExclude;

  private
      $_name,
      $_testMethods;

  final function __construct() {
    self::_Initialize();

    $ro = new \ReflectionObject($this);

    $this->_name = $ro->getName();

    // All public methods in a derived class are considered to be a unit test case.
    $this->_testMethods = \array_filter(
        $ro->getMethods(\ReflectionMethod::IS_PUBLIC), function($_method_) {
      return !\array_key_exists($_method_->getName(), self::$_MethodNamesToExclude);
    });

    if (empty($this->_testMethods)) {
      throw new TestSetException(
      \sprintf(
          'No test case found in "%s". Your TestSuite MUST contain at least one public method.', $this->_name));
    }
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

  function setup() { }

  function teardown() { }

  // Private methods
  // ---------------

  private static function _Initialize() {
    if (NULL === self::$_MethodNamesToExclude) {
      $rc = new \ReflectionClass(__CLASS__);

      // All methods in TestSuite are to be excluded.
      self::$_MethodNamesToExclude = \array_flip(\array_map(
              function($_method_) {
            return $_method_->getName();
          }, $rc->getMethods()
      ));
    }
  }

}

// File-based test set
// =================================================================================================

class FileTestSet implements ITestSet {
  private
      $_name,
      $_path;

  function __construct($_path_, $_name_ = \NULL) {
    $this->_path = $_path_;
    $this->_name = $_name_ ? : $_path_;
  }

  function getName() {
    return $this->_name;
  }

  function run() {
    Narvalo\DynaLoader::IncludeFile($this->_path);
  }
}

class FileTestSetIterator extends \IteratorIterator {
  function __construct(array $_paths_) {
    parent::__construct(new \ArrayIterator($_paths_));
  }

  function current() {
    return new FileTestSet(parent::current());
  }
}

class InDirectoryFileTestSetIterator extends \RecursiveIteratorIterator {
  function __construct(\RecursiveDirectoryIterator $_it_, $_file_ext_) {
    parent::__construct(new _\FileExtensionRecursiveFilterIterator($_it_, $_file_ext_));
  }

  static function FromPath($_path, $_file_ext_) {
    $it = new \RecursiveDirectoryIterator(
        $_path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);

    return new self($it, $_file_ext_);
  }

  function current() {
    return new FileTestSet(parent::current()->getPathname());
  }
}

// #################################################################################################

namespace Narvalo\Test\Sets\Internal;

class FileExtensionRecursiveFilterIterator extends \RecursiveFilterIterator {
  private
      $_fileExt,
      $_fileExtLength;

  function __construct(\RecursiveDirectoryIterator $_it_, $_file_ext_) {
    parent::__construct($_it_);

    $this->_fileExt = $_file_ext_;
    $this->_fileExtLength = \strlen($_file_ext_);
  }

  function accept() {
    $current = $this->getInnerIterator()->current();

    return $this->hasChildren() || !$current->isFile() || $this->_matchExtension($current->getFilename());
  }

  function getChildren() {
    return new self($this->getInnerIterator()->getChildren(), $this->_fileExt);
  }

  // Private methods
  // ---------------

  private function _matchExtension($_value_) {
    return \substr($_value_, - $this->_fileExtLength) === $this->_fileExt;
  }
}

// EOF
