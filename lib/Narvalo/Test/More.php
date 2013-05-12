<?php

namespace Narvalo\Test;

require_once 'Narvalo\Test\FrameworkBundle.php';

use \Narvalo\Test\Framework;

class More {
  use Framework\TestModule;

  function plan($_how_many_) {
    return $this->getProducer()->plan($_how_many_);
  }

  function skipAll($_reason_) {
    return $this->getProducer()->skipAll($_reason_);
  }

  function assert($_test_, $_description_) {
    return $this->getProducer()->assert($_test_, $_description_);
  }

  /// Compare $_got_ and $_expect_ with ==
  function isEqual($_got_, $_expect_, $_description_) {
    $passed = $this->getProducer()->assert($_got_ == $_expect_, $_description_);
    if (!$passed) {
      $this->_diagnoseIsEqual($_got_, $_expect_);
    }
    return $passed;
  }

  /// Compare $_got_ and $_expect_ with !=
  function isNotEqual($_got_, $_expect_, $_description_) {
    $passed = $this->getProducer()->assert($_got_ != $_expect_, $_description_);
    if (!$passed) {
      $this->_diagnoseIsNotEqual($_got_, $_expect_);
    }
    return $passed;
  }

  function compare($_got_, $_type_, $_expect_, $_description_) {
    switch ($_type_) {
    case '==':
      $passed = $_got_ == $_expect_;
      break;
    case '===':
      $passed = $_got_ === $_expect_;
      break;
    case '!=':
      $passed = $_got_ != $_expect_;
      break;
    case '<>':
      $passed = $_got_ <> $_expect_;
      break;
    case '!==':
      $passed = $_got_ !== $_expect_;
      break;
    case '<':
      $passed = $_got_ < $_expect_;
      break;
    case '>':
      $passed = $_got_ > $_expect_;
      break;
    case '<=':
      $passed = $_got_ <= $_expect_;
      break;
    case '>=':
      $passed = $_got_ >= $_expect_;
      break;
    default:
      $this->getProducer()->assert(\FALSE, $_description_);
      $this->getProducer()->diagnose("Unrecognized comparison operator: {$_type_}");
      return;
    }

    $this->getProducer()->assert($passed, $_description_);

    if (!$passed) {
      switch ($_type_) {
      case '==':
      case '===':
        $this->_diagnoseIsEqual($_got_, $_expect_);
        break;
      case '!=':
      case '!==':
      case '<>':
        $this->_diagnoseIsNotEqual($_got_);
        break;
      default:
        $this->_diagnoseCompare($_got_, $_type_, $_expect_);
      }
    }
  }

  function like($_subject_, $_pattern_, $_description_) {
    $test = 1 === \preg_match($_pattern_, $_subject_);
    $passed = $this->getProducer()->assert($test, $_description_);
    if (!$passed) {
      // XXX
    }
    return $passed;
  }

  function unlike($_subject_, $_pattern_, $_description_) {
    $test = 0 === \preg_match($_pattern_, $_subject_);
    $passed = $this->getProducer()->assert($test, $_description_);
    if (!$passed) {
      // XXX
    }
    return $passed;
  }

  function canInclude($_library_, $_description_) {
    // FIXME:
    // - what about includes that return FALSE?
    // - include once or not ?
    // We turn off error reporting otherwise we will have duplicate errors.
    // We eval the code otherwise the include call may abort the whole
    // script.
    $errlevel = \ini_get('error_reporting');
    \error_reporting(0);
    $test = eval('return (\FALSE !== (include_once $_library_))');
    $passed = $this->getProducer()->assert($test, $_description_);
    \error_reporting($errlevel);
    return $passed;
  }

  function canRequire($_library_, $_description_) {
    $errlevel = \ini_get('error_reporting');
    \error_reporting(0);
    $test = eval('return (\FALSE !== (require_once $_library_))');
    $passed = $this->getProducer()->assert($test, $_description_);
    \error_reporting($errlevel);
    return $passed;
  }

  function hasMethod($_class_, $_method_, $_description_) {
    // FIXME: check that the class exists
    $rc = new \ReflectionClass($_class_);
    return $this->getProducer()->assert($rc->hasMethod($_method_), $_description_);
  }

  function implementsInterface($_class_, $_interface_, $_description_) {
    // FIXME: __autoload
    // check that we do have an interface by reflection or via
    // interface_exists()
    $rc = new \ReflectionClass($_class_);
    return $this->getProducer()->assert($rc->implementsInterface($_interface_), $_description_);
  }

  function isInstanceOf($_object_, $_class_, $_description_) {
    // FIXME: __autoload
    return $this->getProducer()->assert($_object_ instanceof $_class_, $_description_);
  }

  function pass($_description_) {
    return $this->getProducer()->assert(\TRUE, $_description_);
  }

  function fail($_description_) {
    return $this->getProducer()->assert(\FALSE, $_description_);
  }

  function subTest(Framework\TestModule $_m_, $_code_, $_description_) {
    return $this->getProducer()->SubTest($_m_, $_code_, $_description_);
  }

  function startTodo($_reason_) {
    return $this->getProducer()->StartTodo($_reason_);
  }

  function endTodo() {
    return $this->getProducer()->EndTodo();
  }

  function skip($_how_many_, $_reason_) {
    return $this->getProducer()->skip($_how_many_, $_reason_);
  }

  function skipSubTest(Framework\TestModule $_m_, $_code_, $_description_, $_reason_) {
    return $this->getProducer()->skipSubTest($_reason_);
  }

  function bailOut($_reason_) {
    return $this->getProducer()->bailOut($_reason_);
  }

  function note($_note_) {
    return $this->getProducer()->note($_note_);
  }

  private function _diagnoseCompare($_got_, $_type_, $_expect_) {
    $got    = \NULL === $_got_    ? 'NULL' : "'$_got_'";
    $expect = \NULL === $_expect_ ? 'NULL' : "'$_expect_'";

    $diag = <<<EOL
    $got
        $_type_
    $expect
EOL;
    $this->getProducer()->diagnose($diag);
  }

  private function _diagnoseIsEqual($_got_, $_expect_) {
    $got    = \NULL === $_got_    ? 'NULL' : "'$_got_'";
    $expect = \NULL === $_expect_ ? 'NULL' : "'$_expect_'";

    $diag = <<<EOL
         got: $got
    expected: $expect
EOL;
    $this->getProducer()->diagnose($diag);
  }

  private function _diagnoseIsNotEqual($_got_) {
    $got = \NULL === $_got_ ? 'NULL' : "'$_got_'";

    $diag = <<<EOL
         got: $got
    expected: anything else
EOL;
    $this->getProducer()->diagnose($diag);
  }
}

// EOF
