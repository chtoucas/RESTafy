<?php

namespace Narvalo\Test\Suites;

require_once 'NarvaloBundle.php';

use \Narvalo;
use \Narvalo\Test\Suites\Internal as _;

// Core classes
// =================================================================================================

// {{{ TestSuite

interface TestSuite {
  function getName();

  function setup();
  function execute();
  function teardown();
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ AbstractTestSuite

abstract class AbstractTestSuite implements TestSuite {
  protected function __construct() {
    ;
  }

  abstract function execute();

  abstract function getName();

  function setup() {
    ;
  }

  function teardown() {
    ;
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ FileTestSuite

class FileTestSuite extends AbstractTestSuite {
  private $_path;

  function __construct($_path_) {
    $this->_path = $_path_;
  }

  function getName() {
    return $this->_path;
  }

  function execute() {
    Narvalo\DynaLoader::LoadFile($this->_path);
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Iterators
// =================================================================================================

// {{{ TestSuiteIterator

// FIXME: This is really a bad design!!!!
interface TestSuiteIterator extends \Iterator { }

// }}} ---------------------------------------------------------------------------------------------
// {{{ TestSuiteIteratorFactory

final class TestSuiteIteratorFactory {
  static function CreateFromDirectory($_directory_, $_file_ext_) {
    $it  = new \RecursiveDirectoryIterator($_directory_, \FilesystemIterator::SKIP_DOTS);

    return new TestSuiteIteratorFromDirectory($it, $_file_ext_);
  }

  static function CreateFromPaths(array $_paths_) {
    return new TestSuiteIteratorFromPaths($_paths_);
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ TestSuiteIteratorFromPaths

class TestSuiteIteratorFromPaths extends \IteratorIterator implements TestSuiteIterator {
  function __construct(array $_paths_) {
    parent::__construct(new \ArrayIterator($_paths_));
  }

  function current() {
    return new FileTestSuite(parent::current());
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ TestSuiteIteratorFromDirectory

class TestSuiteIteratorFromDirectory
  extends \RecursiveIteratorIterator
  implements TestSuiteIterator
{
  function __construct(\RecursiveDirectoryIterator $_it_, $_file_ext_) {
    parent::__construct(new _\RecursiveFileExtensionFilterIterator($_it_, $_file_ext_));
  }

  function current() {
    return new FileTestSuite(parent::current()->getPathname());
  }
}

// }}} ---------------------------------------------------------------------------------------------

// #################################################################################################

namespace Narvalo\Test\Suites\Internal;

// {{{ RecursiveFileExtensionFilterIterator

class RecursiveFileExtensionFilterIterator extends \RecursiveFilterIterator {
  private
    $_fileExt,
    $_length;

  function __construct(\RecursiveDirectoryIterator $_it_, $_file_ext_) {
    parent::__construct($_it_);

    $this->_fileExt = $_file_ext_;
    $this->_length  = \strlen($_file_ext_);
  }

  function accept() {
    $current = $this->getInnerIterator()->current();

    return $this->hasChildren() || !$current->isFile() || $this->_match($current->getFilename());
  }

  function getChildren() {
    return new self($this->getInnerIterator()->getChildren(), $this->_fileExt);
  }

  private function _match($_value_) {
    return \substr($_value_, - $this->_length) === $this->_fileExt;
  }
}

// }}} ---------------------------------------------------------------------------------------------

// EOF
