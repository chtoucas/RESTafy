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

// {{{ TestSetIterator

// FIXME: This is really a bad design!!!!
interface TestSetIterator extends \Iterator { }

// }}} ---------------------------------------------------------------------------------------------

// xUnit-like test set
// =================================================================================================

// {{{ TestSuite

class TestSuite implements TestSet {
  protected function __construct() {
    ;
  }

  function getName() {
    throw new Narvalo\NotImplementedException();
  }

  function run() {
    setup();
    run_();
    teardown();
  }

  function run_() {
    throw new Narvalo\NotImplementedException();
  }

  // Fixtures.

  function setup() {
    ;
  }

  function teardown() {
    ;
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

class FileTestSetIterator extends \IteratorIterator implements TestSetIterator {
  function __construct(array $_paths_) {
    parent::__construct(new \ArrayIterator($_paths_));
  }

  function current() {
    return new FileTestSet(parent::current());
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ InDirectoryFileTestSetIterator

class InDirectoryFileTestSetIterator extends \RecursiveIteratorIterator implements TestSetIterator {
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
