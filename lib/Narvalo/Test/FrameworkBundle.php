<?php

namespace Narvalo\Test\Framework;

require_once 'NarvaloBundle.php';

use \Narvalo;
use \Narvalo\Test\Framework\Internal as _;

// {{{ TestCase

interface TestCase {
  /// The test's description
  /// \return string
  function getDescription();

  /// TRUE if the test passed, FALSE otherwise
  /// \return boolean
  function passed();
}

// }}} #############################################################################################
// {{{ DefaultTestCase

final class DefaultTestCase implements TestCase {
  private
    $_description,
    $_passed;

  function __construct($_description_, $_passed_) {
    $this->_description = empty($_description_) ? 'unamed test' : $_description_;
    $this->_passed      = $_passed_;
  }

  function getDescription() {
    return $this->_description;
  }

  function passed() {
    return $this->_passed;
  }
}

// }}} #############################################################################################
// {{{ AbstractTestCase

abstract class AbstractTestCase implements TestCase {
  private $_reason;

  protected function __construct($_reason_) {
    $this->_reason = $_reason_;
  }

  function getReason() {
    return $this->_reason;
  }
}

// }}} #############################################################################################
// {{{ SkipTestCase

final class SkipTestCase extends AbstractTestCase {
  function __construct($_reason_) {
    parent::__construct($_reason_);
  }

  function getDescription() {
    return '';
  }

  function passed() {
    return \TRUE;
  }
}

// }}} #############################################################################################
// {{{ TodoTestCase

final class TodoTestCase extends AbstractTestCase {
  private $_inner;

  function __construct(TestCase $_inner_, $_reason_) {
    parent::__construct($_reason_);

    $this->_inner  = $_inner_;
  }

  function getDescription() {
    return $this->_inner->getDescription();
  }

  function passed() {
    return $this->_inner->passed();
  }
}

// }}} #############################################################################################

// {{{ FileStreamWriterException

class FileStreamWriterException extends Narvalo\Exception { }

// }}} #############################################################################################
// {{{ TestOutStream

interface TestOutStream {
  function close();
  function reset();
  function canWrite();

  function startSubTest();
  function endSubTest();

  function writeHeader();
  function writeFooter();
  function writePlan($_num_of_tests_);
  function writeSkipAll($_reason_);
  function writeTestCase(TestCase $_test_, $_number_);
  function writeTodoTestCase(TodoTestCase $_test_, $_number_);
  function writeSkipTestCase(SkipTestCase $_test_, $_number_);
  function writeBailOut($_reason_);
  function writeComment($_comment_);
}

// }}} #############################################################################################
// {{{ TestErrStream

interface TestErrStream {
  function close();
  function reset();
  function canWrite();

  function startSubTest();
  function endSubTest();

  function write($_value_);
}

// }}} #############################################################################################
// {{{ FileStreamWriter

class FileStreamWriter {
  private
    $_handle,
    $_opened = \FALSE;

  function __construct($_path_) {
    // Open the handle
    $handle = \fopen($_path_, 'w');
    if (\FALSE === $handle) {
      throw new FileStreamWriterException("Unable to open '{$_path_}' for writing");
    }
    $this->_opened = \TRUE;
    $this->_handle = $handle;
  }

  function __destruct() {
    $this->cleanup_(\FALSE);
  }

  function close() {
    $this->cleanup_(\TRUE);
  }

  function opened() {
    return $this->_opened;
  }

  function canWrite() {
    return $this->_opened && 0 === \fwrite($this->_handle, '');
  }

  function write($_value_) {
    return \fwrite($this->_handle, $_value_);
  }

  function writeLine($_value_) {
    return $this->write($_value_ . \PHP_EOL);
  }

  protected function cleanup_($_disposing_) {
    if (!$this->_opened) {
      return;
    }
    if (\TRUE === \fclose($this->_handle)) {
      $this->_opened = \FALSE;
    }
  }
}

// }}} #############################################################################################

// {{{ TestResult

final class TestResult {
  public
    $passed = \FALSE,
    $bailedOut = \FALSE,
    $runtimeErrorCount = 0,
    $failuresCount = 0,
    $testsCount = 0;
}

// }}} #############################################################################################

// {{{ TestProducerInterrupt

class TestProducerInterrupt extends Narvalo\Exception { }

// }}} #############################################################################################
// {{{ SkipAllTestProducerInterrupt

class SkipAllTestProducerInterrupt extends TestProducerInterrupt { }

// }}} #############################################################################################
// {{{ BailOutTestProducerInterrupt

class BailOutTestProducerInterrupt extends TestProducerInterrupt { }

// }}} #############################################################################################
// {{{ TestProducer

class TestProducer {
  private
    // Error stream.
    $_errStream,
    // Out stream.
    $_outStream,
    // Test set.
    $_set,
    // Test workflow.
    $_workflow,
    // Was the producer interrupted, ie did we throw a TestProducerInterrupt exception?
    $_interrupted       = \FALSE,
    $_bailedOut         = \FALSE,
    $_runtimeErrorsCount = 0,
    // TO-DO stack level
    $_todoLevel         = 0,
    // TO-DO reason
    $_todoReason        = '',
    // TO-DO stack
    $_todoStack         = array();

  function __construct(TestOutStream $_outStream_, TestErrStream $_errStream_) {
    $this->_outStream = $_outStream_;
    $this->_errStream = $_errStream_;
    // NB: Until we have a plan, we use a dynamic test set.
    $this->_set       = new _\DynamicTestSet();
    $this->_workflow  = new _\TestWorkflow();
  }

  // Properties.

  function bailedOut() {
    return $this->_bailedOut;
  }

  function passed() {
    return 0 === $this->_runtimeErrorsCount && !$this->_bailedOut && $this->_set->passed();
  }

  function getTestsCount() {
    return $this->_set->getTestsCount();
  }

  function getFailuresCount() {
    return $this->_set->getFailuresCount();
  }

  function getRuntimeErrorsCount() {
    return $this->_runtimeErrorsCount;
  }

  //

  function startup() {
    $this->_addHeader();
  }

  function bailOutOnException(\Exception $_ex_) {
    $this->_bailedOut = \TRUE;
    $this->_addBailOut($_ex_->getMessage());
    $this->_addFooter();
    $this->_bailOutInterrupt(\FALSE);
  }

  function captureRuntimeError($_error_) {
    $this->_runtimeErrorsCount++;
    $this->_addError($_error_);
  }

  function shutdown() {
    if (!$this->_interrupted) {
      $this->_endTestSet();
      $this->_addFooter();
    }

    $this->shutdownCore_();

    $result = $this->_createResult();

    $this->_reset();

    return $result;
  }

  // Test methods.

  function skipAll($_reason_) {
    $this->_set = new _\EmptyTestSet();
    $this->_addSkipAll($_reason_);
    $this->_addFooter();
    $this->_skipAllInterrupt();
  }

  function bailOut($_reason_) {
    $this->_bailedOut = \TRUE;
    $this->_addBailOut($_reason_);
    $this->_addFooter();
    $this->_bailOutInterrupt();
  }

  function plan($_how_many_) {
    if (!self::_IsStrictlyPositiveInteger($_how_many_)) {
      // Invalid argument exception
      $this->bailOut('Number of tests must be a strictly positive integer. '
        . "You gave it '$_how_many_'.");
    }
    $this->_set = new _\FixedSizeTestSet($_how_many_);
    $this->_addPlan($_how_many_);
  }

  /// Evaluates the expression $_test_, if TRUE reports success,
  /// otherwise reports a failure.
  /// \code
  ///     assert($got === $expected, $test_name);
  /// \endcode
  /// \param $_test_ <boolean> Expression to test
  /// \param $_description_ <string> Test description
  /// \return TRUE if test passed, FALSE otherwise
  function assert($_test_, $_description_) {
    $test = new DefaultTestCase($_description_, \TRUE === $_test_);
    if ($this->_inTodo()) {
      $test = new TodoTestCase($test, $this->_todoReason);
      $number = $this->_set->addTest($test);
      $this->_addTodoTestCase($test, $number);
    } else {
      $number = $this->_set->addTest($test);
      $this->_addTestCase($test, $number);
    }

    if (!$test->passed()) {
      // if the test failed, display the source of the prob
      $what = $this->_inTodo() ? '(TODO) test' : 'test';
      $caller = self::_FindCaller();
      $description = $test->getDescription();
      if (empty($description)) {
        $diag = <<<EOL
Failed $what at {$caller['file']} line {$caller['line']}.
EOL;
      } else {
        $diag = <<<EOL
Failed $what '$description'
at {$caller['file']} line {$caller['line']}.
EOL;
      }
      $this->diagnose($diag);
    }

    return $test->passed();
  }

  function skip($_how_many_, $_reason_) {
    if (!self::_IsStrictlyPositiveInteger($_how_many_)) {
      $errmsg = 'The number of skipped tests must be a strictly positive integer';
      if ($this->_set instanceof _\FixedSizeTestSet) {
        $this->bailOut($errmsg);
      } else {
        $this->Warn($errmsg); return;
      }
    }
    if ($this->_inTodo()) {
      $this->bailOut('You can not interlace a SKIP directive with a TODO block');
    }
    $test = new SkipTestCase($_reason_);
    for ($i = 1; $i <= $_how_many_; $i++) {
      $number = $this->_set->addTest($test);
      $this->_addSkipTestCase($test, $number);
    }
  }

  function startTodo($_reason_) {
    if ($this->_inTodo()) {
      // Keep the upper-level TO-DO in memory
      \array_push($this->_todoStack, $this->_todoReason);
    }
    $this->_todoReason = $_reason_;
    $this->_startTodo();
  }

  function endTodo() {
    //if (!$this->inTodo()) {
    //  $this->bailOut('You can not end a TO-DO block if you did not start one before');
    //}
    $this->_endTodo();
    $this->_todoReason = $this->_inTodo() ? \array_pop($this->_todoStack) : '';
  }

  // FIXME: if the subtest exit at any time it will exit the whole test.
  function subTest(TestModule $_m_, $_code_, $_description_) {
    // Switch to a new TestSet.
    $set = $this->_set;
    $this->_set = new _\DynamicTestSet();
    // Notify outputs.
    $this->_startSubTest();
    // Execute the tests.
    $_code_($_m_);
    //
    $this->_endTestSet();
    // Restore outputs.
    $this->_endsubTest();
    //
    $passed = $this->_set->passed();
    // Restore the TestSet.
    $this->_set = $set;
    // Report the result to the parent producer.
    $this->assert($passed, $_description_);
  }

  function skipSubTest($_reason_) {
    // Notify outputs.
    $this->_startSubTest();
    // Skip all tests.
    $this->_addSkipAll($_reason_);
    // Restore outputs.
    $this->_endSubTest();
    // Report the result to the parent producer.
    $this->skip(1, $_reason_);
  }

  function diagnose($_diag_) {
    if ($this->_inTodo()) {
      $this->_addComment($_diag_);
    } else {
      $this->_addError($_diag_);
    }
  }

  function note($_note_) {
    $this->_addComment($_note_);
  }

  function warn($_errmsg_) {
    $this->_addError($_errmsg_);
  }

  protected function shutdownCore_() {
    ;
  }

  // Utilities.

  private static function _FindCaller() {
    $calltree = \debug_backtrace();
    $file = $calltree['2']['file'];
    $line = $calltree['2']['line'];
    return array('file' => $file,  'line' => $line);
  }

  /// \return boolean
  private static function _IsStrictlyPositiveInteger($_value_) {
    return (int)$_value_ === $_value_ && $_value_ > 0;
  }

  private function _createResult() {
    $result = new TestResult();
    $result->passed             = $this->passed();
    $result->bailedOut          = $this->_bailedOut;
    $result->runtimeErrorsCount = $this->_runtimeErrorsCount;
    $result->failuresCount      = $this->getFailuresCount();
    $result->testsCount         = $this->getTestsCount();

    return $result;
  }

  private function _inTodo() {
    return $this->_workflow->inTodo();
  }

  private function _postPlan() {
    if ($this->_set instanceof _\DynamicTestSet
      && ($tests_count = $this->_set->getTestsCount()) > 0
    ) {
      // We actually run tests.
      $this->_addPlan($tests_count);
    }
  }

  private function _endTestSet() {
    $this->_postPlan();

    // Print helpful messages if something went wrong.
    $this->_set->close($this->_errStream);
  }

  private function _reset() {
    $this->_set         = new _\DynamicTestSet();
    $this->_bailedOut   = \FALSE;
    $this->_todoLevel   = 0;
    $this->_todoReason  = '';
    $this->_todoStack   = array();
    $this->_runtimeErrorsCount = 0;
    $this->_interrupted = \FALSE;
    $this->_errStream->reset();
    $this->_outStream->reset();
    $this->_workflow->reset();
  }

  // Producer interrupts.

  private function _interrupt() {
    $this->_interrupted = \TRUE;
    // THIS IS BAD! but I do not see any other simple way to do it.
    throw new TestProducerInterrupt();
  }

  private function _bailOutInterrupt($_throw_ = \TRUE) {
    $this->_interrupted = \TRUE;
    // THIS IS BAD! but I do not see any other simple way to do it.
    if ($_throw_) {
      throw new BailOutTestProducerInterrupt();
    }
  }

  private function _skipAllInterrupt() {
    $this->_interrupted = \TRUE;
    // THIS IS BAD! but I do not see any other simple way to do it.
    throw new SkipAllTestProducerInterrupt();
  }

  // Core methods.

  private function _addHeader() {
    $this->_workflow->enterHeader();
    $this->_outStream->writeHeader();
  }

  private function _addFooter() {
    $this->_workflow->enterFooter();
    $this->_outStream->writeFooter();
  }

  private function _startSubTest() {
    $this->_workflow->startSubTest();
    $this->_outStream->startSubTest();
    $this->_errStream->startSubTest();
  }

  private function _endSubTest() {
    $this->_workflow->endSubTest();
    $this->_outStream->endSubTest();
    $this->_errStream->endSubTest();
  }

  private function _startTodo() {
    $this->_workflow->startTodo();
    //$this->_outStream->startTodo();
    //$this->_errStream->startTodo();
  }

  private function _endTodo() {
    $this->_workflow->endTodo();
    //$this->_outStream->endTodo();
    //$this->_errStream->endTodo();
  }

  private function _addPlan($_num_of_tests_) {
    $this->_workflow->enterPlan();
    $this->_outStream->writePlan($_num_of_tests_);
  }

  private function _addSkipAll($_reason_) {
    $this->_workflow->enterSkipAll();
    $this->_outStream->writeSkipAll($_reason_);
  }

  private function _addTestCase(TestCase $_test_, $_number_) {
    $this->_workflow->enterTestCase();
    $this->_outStream->writeTestCase($_test_, $_number_);
  }

  private function _addTodoTestCase(TodoTestCase $_test_, $_number_) {
    $this->_workflow->enterTestCase();
    $this->_outStream->writeTodoTestCase($_test_, $_number_);
  }

  private function _addSkipTestCase(SkipTestCase $_test_, $_number_) {
    $this->_workflow->enterTestCase();
    $this->_outStream->writeSkipTestCase($_test_, $_number_);
  }

  private function _addBailOut($_reason_) {
    $this->_workflow->enterBailOut();
    $this->_outStream->writeBailOut($_reason_);
  }

  private function _addComment($_comment_) {
    $this->_workflow->enterComment();
    $this->_outStream->writeComment($_comment_);
  }

  private function _addError($_errmsg_) {
    $this->_workflow->enterError();
    $this->_errStream->write($_errmsg_);
  }
}

// }}} #############################################################################################

// {{{ TestModulesKernelException

class TestModulesKernelException extends Narvalo\Exception { }

// }}} #############################################################################################
// {{{ TestModulesKernel

final class TestModulesKernel {
  private static
    $_SharedProducer,
    $_Bootstrapped = \FALSE;

  static function Bootstrap(TestProducer $_producer_, $_throwIfCalledTwice_ = \TRUE) {
    if (self::$_Bootstrapped) {
      if ($_throwIfCalledTwice_) {
        throw new TestModulesKernelException('Kernel already initialized.');
      } else {
        return;
      }
    }

    self::$_SharedProducer = $_producer_;
    if ($_throwIfCalledTwice_) {
      self::$_Bootstrapped = \TRUE;
    }
  }

  static function GetSharedProducer() {
    if (!self::$_Bootstrapped) {
      throw new TestModulesKernelException('Before anything, you must initialize the kernel.');
    }
    return self::$_SharedProducer;
  }
}

// }}} #############################################################################################
// {{{ TestModule

/// NB: TestModule is a Borg: you can create as many instances of any derived
/// class and they will all share the same producer.
trait TestModule {
  private $_producer;

  function getProducer() {
    if (\NULL === $this->_producer) {
      // All modules share the same producer.
      $this->_producer = TestModulesKernel::GetSharedProducer();
    }
    return $this->_producer;
  }
}

// }}} #############################################################################################

namespace Narvalo\Test\Framework\Internal;

use \Narvalo;
use \Narvalo\Test\Framework;

// {{{ AbstractTestSet

abstract class AbstractTestSet {
  private
    // Number of failed tests.
    $_failuresCount = 0,
    // List of tests.
    $_tests = array();

  protected function __construct() {
    ;
  }

  function getTestsCount() {
    return \count($this->_tests);
  }

  function getFailuresCount() {
    return $this->_failuresCount;
  }

  abstract function close(Framework\TestErrStream $_errStream_);

  abstract function passed();

  function addTest(Framework\TestCase $_test_) {
    if (!$_test_->passed()) {
      $this->_failuresCount++;
    }
    $number = $this->getTestsCount();
    $this->_tests[$number] = $_test_;
    return 1 + $number;
  }
}

// }}} #############################################################################################
// {{{ EmptyTestSet

final class EmptyTestSet extends AbstractTestSet {
  function __construct() {
    ;
  }

  function passed() {
    return \TRUE;
  }

  function close(Framework\TestErrStream $_errStream_) {
    ;
  }

  final function addTest(Framework\TestCase $_test_) {
    return 0;
  }
}

// }}} #############################################################################################
// {{{ DynamicTestSet

final class DynamicTestSet extends AbstractTestSet {
  function __construct() {
    ;
  }

  function close(Framework\TestErrStream $_errStream_) {
    if (($tests_count = $this->getTestsCount()) > 0) {
      // We actually run tests.
      if (($failures_count = $this->getFailuresCount()) > 0) {
        // There are failures.
        $s = $failures_count > 1 ? 's' : '';
        $_errStream_->write("Looks like you failed {$failures_count} test{$s} "
          . "of {$tests_count} run.");
      }
      $_errStream_->write('No plan!');
    } else {
      // No tests run.
      $_errStream_->write('No plan. No tests run!');
    }
  }

  function passed() {
    // We actually run tests and they all passed.
    return 0 === $this->getFailuresCount() && $this->getTestsCount() != 0;
  }
}

// }}} #############################################################################################
// {{{ FixedSizeTestSet

final class FixedSizeTestSet extends AbstractTestSet {
  // Number of expected tests.
  private $_length;

  function __construct($_length_) {
    $this->_length = $_length_;
  }

  /// \return integer
  function getLength() {
    return $this->_length;
  }

  function getExtrasCount() {
    return $this->getTestsCount() - $this->_length;
  }

  function passed() {
    // We actually run tests, they all passed and there are no extras tests.
    return 0 === $this->getFailuresCount()
      && 0 !== $this->getTestsCount()
      && 0 === $this->getExtrasCount();
  }

  function close(Framework\TestErrStream $_errStream_) {
    //
    if (($tests_count = $this->getTestsCount()) > 0) {
      // We actually run tests.
      $extras_count = $this->getExtrasCount();
      if ($extras_count != 0) {
        // Count missmatch.
        $s = $this->_length > 1 ? 's' : '';
        $_errStream_->write("Looks like you planned {$this->_length} test{$s} "
          . "but ran {$tests_count}.");
      }
      if (($failures_count = $this->getFailuresCount()) > 0) {
        // There are failures.
        $s = $failures_count > 1 ? 's' : '';
        $qualifier = 0 == $extras_count ? '' : ' run';
        $_errStream_->write("Looks like you failed {$failures_count} test{$s} "
          . "of {$tests_count}{$qualifier}.");
      }
    } else {
      // No tests run.
      $_errStream_->write('No tests run!');
    }
  }
}

// }}} #############################################################################################

// {{{ TestWorkflowException

class TestWorkflowException extends Narvalo\Exception { }

// }}} #############################################################################################
// {{{ TestWorkflow

final class TestWorkflow {
  const
    START       = 0,
    HEADER      = 1,
    PLAN        = 2,
    BODY        = 3,
    // Valid end states.
    BODY_PLAN   = 4,
    PLAN_BODY   = 5,
    PLAN_NOBODY = 6,
    BAILOUT     = 7,
    END         = 8;

  private
    $_disposed     = \FALSE,
    $_state        = self::START,
    $_subStates    = array(),
    $_subTestLevel = 0,
    // TO-DO stack level
    $_todoLevel    = 0;

  function __destruct() {
    $this->cleanup_(\FALSE);
  }

  protected function cleanup_($_disposing_) {
    if ($this->_disposed) {
      return;
    }
    // Check workflow's state.
    switch ($this->_state) {
      // Valid end states.
    case self::END:
      break;
    case self::START:
      // XXX reset() or no test at all
      break;
    default:
      \trigger_error('The workflow will end in an invalid state: ' . $this->_state,
        \E_USER_WARNING);
    }
    $this->_disposed = \TRUE;
  }

  function inTodo() {
    return $this->_todoLevel > 0;
  }

  function inSubTest() {
    return $this->_subTestLevel > 0;
  }

  function reset() {
    $this->_state        = self::START;
    $this->_subStates    = array();
    $this->_subTestLevel = 0;
    $this->_todoLevel    = 0;
  }

  function enterHeader() {
    if (self::START === $this->_state) {
      // Allowed state.
      $this->_state = self::HEADER;
    } else {
      // Invalid state.
      throw new TestWorkflowException('The header must come first. Invalid workflow state: ' . $this->_state);
    }
  }

  function enterFooter() {
    // Check workflow's state.
    switch ($this->_state) {
      // Valid end states.
    case self::BAILOUT:
    case self::BODY_PLAN:
    case self::PLAN_BODY:
    case self::PLAN_NOBODY:
      // XXX
    case self::HEADER:
      break;
      // Invalid state.
    case self::END:
      throw new TestWorkflowException('Workflow already ended.');
      //    case self::HEADER:
      //      throw new TestWorkflowException('The workflow will end prematurely.');
    default:
      throw new TestWorkflowException('The workflow will end in an invalid state: ' . $this->_state);
    }
    // Check subtests' level
    if (0 !== $this->_subTestLevel) {
      throw new TestWorkflowException('There is still an opened subtest in the workflow: ' . $this->_subTestLevel);
    }
    // Check TO-DO' level
    if (0 !== $this->_todoLevel) {
      throw new TestWorkflowException('There is still an opened TODO in the workflow: ' . $this->_subTestLevel);
    }
    $this->_state = self::END;
  }

  function startSubTest() {
    switch ($this->_state) {
      // Allowed state.
    case self::HEADER:
    case self::BODY:
    case self::PLAN:
    case self::PLAN_BODY:
      break;
      // Invalid state.
    case self::START:
      throw new TestWorkflowException('Unable to start a subtest: missing header');
    case self::END:
      throw new TestWorkflowException('Unable to start a subtest: workflow ended');
    case self::BODY_PLAN:
      throw new TestWorkflowException('Unable to start a subtest: you already end your tests with a plan');
    case self::PLAN_NOBODY:
      throw new TestWorkflowException('You can not start a subtest and skip all tests at the same time');
    case self::BAILOUT:
      throw new TestWorkflowException('You can not start a subtest after bailing out');
    default:
      throw new TestWorkflowException('Invalid workflow state: ' . $this->_state);
    }
    // FIXME reset TO-DO level?
    \array_push($this->_subStates, $this->_state);
    $this->_state = self::HEADER;
    return ++$this->_subTestLevel;
  }

  /// \return void
  function endSubTest() {
    // FIXME: valid states
    if (0 === $this->_subTestLevel) {
      throw new TestWorkflowException('You can not end a subtest if you did not start one before');
    }
    $this->_state = \array_pop($this->_subStates);
    $this->_subTestLevel--;
  }

  function startTodo() {
    switch ($this->_state) {
      // Allowed state.
    case self::HEADER:
    case self::BODY:
    case self::PLAN:
    case self::PLAN_BODY:
      break;
      // Invalid state.
    case self::START:
      throw new TestWorkflowException('Unable to start a TODO: missing header');
    case self::END:
      throw new TestWorkflowException('Unable to start a TODO: workflow ended');
    case self::BODY_PLAN:
      throw new TestWorkflowException('Unable to start a TODO: you already end your tests with a plan');
    case self::PLAN_NOBODY:
      throw new TestWorkflowException('You can not start a TODO and skip all tests at the same time');
    case self::BAILOUT:
      throw new TestWorkflowException('You can not start a TODO after bailing out');
    default:
      throw new TestWorkflowException('Invalid workflow state: ' . $this->_state);
    }
    return ++$this->_todoLevel;
  }

  function endTodo() {
    // FIXME valid states
    if (0 === $this->_todoLevel) {
      throw new TestWorkflowException('You can not end a TODO if you did not start one before');
    }
    $this->_todoLevel--;
  }

  function enterPlan() {
    switch ($this->_state) {
      // Allowed state.
    case self::HEADER:
      // Static plan.
      $this->_state = self::PLAN;
      break;
    case self::BODY:
      // Dynamic plan.
      $this->_state = self::BODY_PLAN;
      break;
      // Invalid state.
    case self::START:
      throw new TestWorkflowException('You can not plan: missing header');
    case self::END:
      throw new TestWorkflowException('You can not plan: workflow already ended');
    case self::BODY_PLAN:
    case self::PLAN:
    case self::PLAN_BODY:
      throw new TestWorkflowException('You can not plan twice. Invalid workflow state: ' . $this->_state);
    case self::PLAN_NOBODY:
      throw new TestWorkflowException('You can not plan and skip all tests at the same time');
    case self::BAILOUT:
      throw new TestWorkflowException('You can not plan after bailing out');
    default:
      throw new TestWorkflowException('Invalid workflow state: ' . $this->_state);
    }
  }

  function enterSkipAll() {
    switch ($this->_state) {
      // Allowed state.
    case self::HEADER:
      // Skip All plan.
      $this->_state = self::PLAN_NOBODY;
      break;
      // Invalid state.
    case self::START:
      throw new TestWorkflowException('Unable to skip all tests: missing header');
    case self::END:
      throw new TestWorkflowException('Unable to skip all tests: workflow already closed');
    case self::BODY:
      throw new TestWorkflowException('Unable to skip all tests: you already run at least one test');
    case self::BODY_PLAN:
    case self::PLAN:
    case self::PLAN_BODY:
      throw new TestWorkflowException('Unable to skip all tests: you already made a plan. Invalid workflow state: ' . $this->_state);
    case self::PLAN_NOBODY:
      throw new TestWorkflowException('You already asked to skip all tests');
    case self::BAILOUT:
      throw new TestWorkflowException('You can not skip all tests after bailing out');
    default:
      throw new TestWorkflowException('Invalid workflow state: ' . $this->_state);
    }
  }

  function enterTestCase() {
    switch ($this->_state) {
      // Allowed state.
    case self::HEADER:
      // Dynamic plan. First test.
      $this->_state = self::BODY;
      break;
    case self::BODY:
      // Dynamic plan. Later test.
      break;
    case self::PLAN:
      // Static plan. First test.
      $this->_state = self::PLAN_BODY;
      break;
    case self::PLAN_BODY:
      // Static plan. Later test.
      break;
      // Invalid state.
    case self::START:
      throw new TestWorkflowException('Unable to register a test: missing header');
    case self::END:
      throw new TestWorkflowException('Unable to register a test: workflow already ended');
    case self::BODY_PLAN:
      throw new TestWorkflowException('Unable to register a test: you already end your tests with a plan');
    case self::PLAN_NOBODY:
      throw new TestWorkflowException('You can not register a test if you asked to skip all tests');
    case self::BAILOUT:
      throw new TestWorkflowException('You can not register a test after bailing out');
    default:
      throw new TestWorkflowException('Invalid workflow state: ' . $this->_state);
    }
  }

  function enterBailOut() {
    switch ($this->_state) {
      // Allowed state.
    case self::HEADER:
    case self::BODY:
    case self::BODY_PLAN:
    case self::PLAN:
    case self::PLAN_BODY:
    case self::PLAN_NOBODY:
      $this->_state = self::BAILOUT;
      break;
      // Invalid state.
    case self::START:
      throw new TestWorkflowException('You can not bail out: missing header');
    case self::END:
      throw new TestWorkflowException('You can not bail out: workflow already ended');
    case self::BAILOUT:
      throw new TestWorkflowException('You can not bail out twice');
    default:
      throw new TestWorkflowException('Invalid workflow state: ' . $this->_state);
    }
  }

  function enterComment() {
    // Only one invalid state and this method does not change state.
    switch ($this->_state) {
      // Invalid state.
    case self::START:
      throw new TestWorkflowException('You can not write a comment: missing header');
    case self::END:
      throw new TestWorkflowException('You can not write a comment: workflow already ended');
    default:
      break;
    }
  }

  function enterError() {
    // Only one invalid state and this method does not change state.
    switch ($this->_state) {
      // Invalid state.
    case self::START:
      throw new TestWorkflowException('You can not write an error: missing header');
    case self::END:
      throw new TestWorkflowException('You can not write an error: workflow already closed');
    default:
      break;
    }
  }
}

// }}} #############################################################################################

//\note IMPORTANT
//
//DO NOT import any other file, the testing library MUST STAY self contained.
//
//\todo
//
// - noPlan()
// - add a verbose mode for TapRunner
// - check all constructors for validity
// - reset states
// - return values for methods
// - can TAP normal and error streams use the same FH?
// - flush on handles to ensure correct ordering
// - test the test
// - in a subtest, we should unindent in bailout
// - how to catch exceptions so that they do not garble the output
// - Test::Harness, Test::Differences, Test::Deeper, Test::Class, Test::Most
// - doc: code, usage, diff with Test::More, error reporting
//
//\par ERROR REPORTING
//
//Several ways to report an error:
// - throws an Exception for any internal error and for fatal error
//    where we can not use TestProducer::bailOut()
// - trigger_error()
//    * \c E_USER_ERROR for any fatal error during GC
//    * \c E_USER_WARNING for any non-fatal error where we can not use \c TestProducer::Warn()
// - \c TestProducer::bailOut() for remaining fatal errors
// - \c TestProducer::Warn() for remaining non-fatal errors

// EOF
