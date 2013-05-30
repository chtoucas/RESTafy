<?php

namespace Narvalo\Test;

require_once 'NarvaloBundle.php';
require_once 'Narvalo/Test/FrameworkBundle.php';

use \Narvalo;
use \Narvalo\Test\Framework;

class More extends Framework\TestModule {
  // TODO: Test deep object graph.
  // TODO: Test exceptions thrown.
  // TODO: Test dynamicly loaded extensions.
  // TODO: Add more diagnostics.
  // TODO: Cf. Test::Differences, Test::Deeper, Test::Class, Test::Most

  function __construct($_how_many_ = \NULL) {
    parent::__construct();

    if (\NULL !== $_how_many_) {
      $this->plan($_how_many_);
    }
  }

  // Test declaration of intentions
  // ------------------------------

  function plan($_how_many_) {
    return $this->getProducer()->plan($_how_many_);
  }

  function skipAll($_reason_) {
    return $this->getProducer()->skipAll($_reason_);
  }

  // Basic test methods
  // ------------------

  function ok($_test_, $_name_) {
    return $this->getProducer()->assert($_test_, $_name_);
  }

  function pass($_name_) {
    return $this->ok(\TRUE, $_name_);
  }

  function fail($_name_) {
    return $this->ok(\FALSE, $_name_);
  }

  /// Compare $_got_ and $_expected_ with ===
  function is($_got_, $_expected_, $_name_) {
    $test = $_got_ === $_expected_;
    $passed = $this->ok($test, $_name_);
    if (!$passed) {
      $this->_diagnoseFailedEquality($_got_, $_expected_);
    }
    return $passed;
  }

  /// Compare $_got_ and $_expected_ with !==
  function isnt($_got_, $_expected_, $_name_) {
    $test = $_got_ !== $_expected_;
    $passed = $this->ok($test, $_name_);
    if (!$passed) {
      $this->_diagnoseFailedInequality($_got_, $_expected_);
    }
    return $passed;
  }

  function cmp($_got_, $_type_, $_expected_, $_name_) {
    // Another way to do that: eval("return (\$_got_ $_operator_ \$_expected_);")
    switch ($_type_) {
    case '==':  $test = $_got_ ==  $_expected_; break;
    case '===': $test = $_got_ === $_expected_; break;
    case '!=':  $test = $_got_ !=  $_expected_; break;
    case '<>':  $test = $_got_ <>  $_expected_; break;
    case '!==': $test = $_got_ !== $_expected_; break;
    case '<':   $test = $_got_ <   $_expected_; break;
    case '>':   $test = $_got_ >   $_expected_; break;
    case '<=':  $test = $_got_ <=  $_expected_; break;
    case '>=':  $test = $_got_ >=  $_expected_; break;
    default:
      $this->fail($_name_);
      $this->getProducer()->diagnose(\sprintf('Unrecognized comparison operator: "%s".', $_type_));
      return;
    }

    $passed = $this->ok($test, $_name_);

    if (!$passed) {
      switch ($_type_) {
      case '==':
      case '===':
        $this->_diagnoseFailedEquality($_got_, $_expected_);
        break;
      case '!=':
      case '!==':
      case '<>':
        $this->_diagnoseFailedInequality($_got_);
        break;
      default:
        $this->_diagnoseFailedCompare($_got_, $_type_, $_expected_);
      }
    }

    return $passed;
  }

  function like($_subject_, $_pattern_, $_name_) {
    $test = 1 === \preg_match($_pattern_, $_subject_);
    $passed = $this->ok($test, $_name_);
    if (!$passed) {
      $this->_diagnoseFailedMatch($_subject_, $_pattern_);
    }
    return $passed;
  }

  function unlike($_subject_, $_pattern_, $_name_) {
    $test = 0 === \preg_match($_pattern_, $_subject_);
    $passed = $this->ok($test, $_name_);
    if (!$passed) {
      $this->_diagnoseFailedUnmatch($_subject_, $_pattern_);
    }
    return $passed;
  }

  function subtest($_name_, \Closure $_fun_) {
    return $this->getProducer()->subtest($_fun_, $_name_);
  }

  // Test library availability
  // -------------------------

  function canInclude($_library_, $_name_) {
    $test = Narvalo\DynaLoader::TryLoadLibrary($_library_);
    return $this->ok($test, $_name_);
  }

//  function canRequire($_library_, $_name_) {
//    $test = eval('return (\FALSE !== (require_once $_library_))');
//    return $this->ok($test, $_name_);
//  }

  // Object testing
  // --------------

  function isa($_object_, $_class_, $_name_) {
    $test = $_object_ instanceof $_class_;
    return $this->ok($test, $_name_);
  }

  function can($_object_, $_method_, $_name_) {
    $ro = new \ReflectionObject($_object_);
    $test = $ro->hasMethod($_method_);
    return $this->ok($test, $_name_);
  }

  function implementsInterface($_object_, $_interface_, $_name_) {
    // FIXME: Check that we do have an interface by reflection or via interface_exists()?
    $ro = new \ReflectionObject($_object_);
    $test = $ro->implementsInterface($_interface_);
    return $this->ok($test, $_name_);
  }

  // Utilities
  // ---------

  function startTodo($_reason_) {
    $todo = new Framework\TagTestDirective($_reason_, 'TODO');
    $this->getProducer()->startTagging($todo);
    return $todo;
  }

  function endTodo($_todo_) {
    $this->getProducer()->endTagging($_todo_);
  }

  function skip($_how_many_, $_reason_) {
    $this->getProducer()->skip($_how_many_, new Framework\SkipTestDirective($_reason_, 'SKIP'));
  }

  function skipTodo($_how_many_, $_reason_) {
    $this->getProducer()->skip(
      $_how_many_, new Framework\SkipTestDirective($_reason_, 'SKIP & TODO'));
  }

  function bailOut($_reason_) {
    $this->getProducer()->bailOut($_reason_);
  }

  function note($_note_) {
    $this->getProducer()->note($_note_);
  }

  // Private methods
  // ---------------

  private function _diagnoseFailedEquality($_got_, $_expected_) {
    $got    = \NULL === $_got_      ? 'NULL' : (\is_object($_got_)      ? 'Object' : "'$_got_'");
    $expect = \NULL === $_expected_ ? 'NULL' : (\is_object($_expected_) ? 'Object' : "'$_expected_'");

    $diag = <<<EOL
         got: $got
    expected: $expect
EOL;
    $this->getProducer()->diagnose($diag);
  }

  private function _diagnoseFailedInequality($_got_) {
    $got = \NULL === $_got_ ? 'NULL' : (\is_object($_got_) ? 'Object' : "'$_got_'");

    $diag = <<<EOL
         got: $got
    expected: anything else
EOL;
    $this->getProducer()->diagnose($diag);
  }

  private function _diagnoseFailedCompare($_got_, $_type_, $_expected_) {
    $got    = \NULL === $_got_      ? 'NULL' : (\is_object($_got_)      ? 'Object' : "'$_got_'");
    $expect = \NULL === $_expected_ ? 'NULL' : (\is_object($_expected_) ? 'Object' : "'$_expected_'");

    $diag = <<<EOL
    $got
        $_type_
    $expect
EOL;
    $this->getProducer()->diagnose($diag);
  }

  private function _diagnoseFailedMatch($_subject_, $_pattern_) {
    $diag = <<<EOL
              got: $_subject_
    doesn't match: $_pattern_
EOL;
    $this->getProducer()->diagnose($diag);
  }

  private function _diagnoseFailedUnmatch($_subject_, $_pattern_) {
    $diag = <<<EOL
      got: $_subject_
    match: $_pattern_
EOL;
    $this->getProducer()->diagnose($diag);
  }
}

// EOF
