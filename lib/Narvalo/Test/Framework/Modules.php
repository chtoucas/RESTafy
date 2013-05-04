<?php

namespace Narvalo\Test\Framework\Modules;

require_once 'Narvalo\Test\Framework.php';

use Narvalo\Test\Framework\Test as Test;

abstract class TestSpecBase {
  protected function __construct() {
    ;
  }

  abstract protected function runScenario();

  static function AutoRun() {
    $spec = new static();
    $spec->Run();
  }

  static function RunInline($_scenario_) {
    self::Begin();
    $_scenario_();
    self::End();
  }

  protected static function Begin() {
    Test\TestModule::SharedProducer()->Startup();
  }

  protected static function End() {
    Test\TestModule::SharedProducer()->Shutdown();
  }

  function run() {
    self::Begin();
    $this->RunScenario();
    self::End();
  }
}

/// This class should get you up to speed.
/// Once you are familiar with the TAP protocol, you should move
/// to one of the other Test Modules.
class Simple extends Test\TestModule {
  function __construct($_how_many_) {
    parent::__construct();
    $this->Producer()->Plan($_how_many_);
  }

  function assert($_test_, $_description_) {
    return $this->Producer()->Assert($_test_, $_description_);
  }
}

class More extends Test\TestModule {
  function plan($_how_many_) {
    return $this->Producer()->Plan($_how_many_);
  }

  function skipAll($_reason_) {
    return $this->Producer()->SkipAll($_reason_);
  }

  function assert($_test_, $_description_) {
    return $this->Producer()->Assert($_test_, $_description_);
  }

  /// Compare $_got_ and $_expect_ with ==
  function isEqual($_got_, $_expect_, $_description_) {
    $passed = $this->Producer()->Assert($_got_ == $_expect_, $_description_);
    if (!$passed) {
      $this->_diagnoseIsEqual($_got_, $_expect_);
    }
    return $passed;
  }

  /// Compare $_got_ and $_expect_ with !=
  function isNotEqual($_got_, $_expect_, $_description_) {
    $passed = $this->Producer()->Assert($_got_ != $_expect_, $_description_);
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
      $this->Producer()->Assert(FALSE, $_description_);
      $this->Producer()->diagnose("Unrecognized comparison operator: {$_type_}");
      return;
    }

    $this->Producer()->Assert($passed, $_description_);

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
    $test = 1 === preg_match($_pattern_, $_subject_);
    $passed = $this->Producer()->Assert($test, $_description_);
    if (!$passed) {
      // XXX
    }
    return $passed;
  }

  function unlike($_subject_, $_pattern_, $_description_) {
    $test = 0 === preg_match($_pattern_, $_subject_);
    $passed = $this->Producer()->Assert($test, $_description_);
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
    $errlevel = ini_get('error_reporting');
    error_reporting(0);
    $test = eval('return (FALSE !== (include_once $_library_))');
    $passed = $this->Producer()->Assert($test, $_description_);
    error_reporting($errlevel);
    return $passed;
  }

  function canRequire($_library_, $_description_) {
    $errlevel = ini_get('error_reporting');
    error_reporting(0);
    $test = eval('return (FALSE !== (require_once $_library_))');
    $passed = $this->Producer()->Assert($test, $_description_);
    error_reporting($errlevel);
    return $passed;
  }

  function hasMethod($_class_, $_method_, $_description_) {
    // FIXME: check that the class exists
    $rc = new ReflectionClass($_class_);
    return $this->Producer()->Assert($rc->hasMethod($_method_), $_description_);
  }

  function implementsInterface($_class_, $_interface_, $_description_) {
    // FIXME: __autoload
    // check that we do have an interface by reflection or via
    // interface_exists()
    $rc = new ReflectionClass($_class_);
    return $this->Producer()->Assert($rc->implementsInterface($_interface_), $_description_);
  }

  function isInstanceOf($_object_, $_class_, $_description_) {
    // FIXME: __autoload
    return $this->Producer()->Assert($_object_ instanceof $_class_, $_description_);
  }

  function pass($_description_) {
    return $this->Producer()->Assert(TRUE, $_description_);
  }

  function fail($_description_) {
    return $this->Producer()->Assert(FALSE, $_description_);
  }

  function subTest(Test\TestModule $_m_, $_code_, $_description_) {
    return $this->Producer()->SubTest($_m_, $_code_, $_description_);
  }

  function startTodo($_reason_) {
    return $this->Producer()->StartTodo($_reason_);
  }

  function endTodo() {
    return $this->Producer()->EndTodo();
  }

  function skip($_how_many_, $_reason_) {
    return $this->Producer()->Skip($_how_many_, $_reason_);
  }

  function skipSubTest(Test\TestModule $_m_, $_code_, $_description_, $_reason_) {
    return $this->Producer()->SkipSubTest($_reason_);
  }

  function bailOut($_reason_) {
    return $this->Producer()->BailOut($_reason_);
  }

  function note($_note_) {
    return $this->Producer()->Note($_note_);
  }

  private function _diagnoseCompare($_got_, $_type_, $_expect_) {
    $got    = NULL === $_got_    ? 'NULL' : "'$_got_'";
    $expect = NULL === $_expect_ ? 'NULL' : "'$_expect_'";

    $diag = <<<EOL
    $got
        $_type_
    $expect
EOL;
    $this->Producer()->diagnose($diag);
  }

  private function _diagnoseIsEqual($_got_, $_expect_) {
    $got    = NULL === $_got_    ? 'NULL' : "'$_got_'";
    $expect = NULL === $_expect_ ? 'NULL' : "'$_expect_'";

    $diag = <<<EOL
         got: $got
    expected: $expect
EOL;
    $this->Producer()->diagnose($diag);
  }

  private function _diagnoseIsNotEqual($_got_) {
    $got = NULL === $_got_ ? 'NULL' : "'$_got_'";

    $diag = <<<EOL
         got: $got
    expected: anything else
EOL;
    $this->Producer()->diagnose($diag);
  }
}

/// See Test::Deeper
class Deeper extends Test\TestModule {
}

/// See Tests::Differences & Test::Most
class Most extends Test\TestModule {
}

// ########################################################################################

// EOF
