<?php

namespace Narvalo\Test\Framework;

require_once 'NarvaloBundle.php';

use \Narvalo;
use \Narvalo\Test\Framework\Internal as _;

// Test directives
// =================================================================================================

// {{{ TestDirective_

abstract class TestDirective_ {
  private
    $_name,
    $_reason;

  protected function __construct($_reason_, $_name_) {
    $this->_reason = $_reason_;
    $this->_name   = $_name_;
  }

  abstract function apply(TestCaseResult $_test_);

  function getName() {
    return $this->_name;
  }

  function getReason() {
    return $this->_reason;
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ MarkTestDirective

class MarkTestDirective extends TestDirective_ {
  function __construct($_reason_, $_name_) {
    parent::__construct($_reason_, $_name_);
  }

  final function apply(TestCaseResult $_test_) {
    return $_test_->passed();
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ DitchTestDirective

class DitchTestDirective extends TestDirective_ {
  function __construct($_reason_, $_name_) {
    parent::__construct($_reason_, $_name_);
  }

  final function apply(TestCaseResult $_test_) {
    return \TRUE;
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ TodoTestDirective

class TodoTestDirective extends MarkTestDirective {
  function __construct($_reason_) {
    parent::__construct($_reason_, 'TODO');
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ SkipTestDirective

class SkipTestDirective extends DitchTestDirective {
  function __construct($_reason_) {
    parent::__construct($_reason_, 'SKIP');
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ SkipTodoTestDirective

class SkipTodoTestDirective extends DitchTestDirective {
  function __construct($_reason_) {
    parent::__construct($_reason_, 'SKIP & TODO');
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Test results
// =================================================================================================

// {{{ TestCaseResult

class TestCaseResult {
  private
    $_description,
    $_passed;

  function __construct($_description_, $_passed_) {
    $this->_description = $_description_;
    $this->_passed      = $_passed_;
  }

  /// The test's description.
  function getDescription() {
    return $this->_description;
  }

  /// TRUE if the test passed, FALSE otherwise.
  function passed() {
    return $this->_passed;
  }

  function ditch(DitchTestDirective $_directive_) {
    return new AlteredTestCaseResult($this, $_directive_);
  }

  function mark(MarkTestDirective $_directive_) {
    return new AlteredTestCaseResult($this, $_directive_);
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ AlteredTestCaseResult

class AlteredTestCaseResult {
  private
    $_directive,
    $_inner;

  function __construct(TestCaseResult $_inner_, TestDirective_ $_directive_) {
    $this->_inner     = $_inner_;
    $this->_directive = $_directive_;
  }

  function getDescription() {
    return $this->_inner->getDescription();
  }

  function getAlterationName() {
    return $this->_directive->getName();
  }

  function getAlterationReason() {
    return $this->_directive->getReason();
  }

  function passed() {
    return $this->_directive->apply($this->_inner);
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ TestSetResult

final class TestSetResult {
  public
    $passed             = \FALSE,
    $bailedOut          = \FALSE,
    $runtimeErrorsCount = 0,
    $failuresCount      = 0,
    $testsCount         = 0;
}

// }}} ---------------------------------------------------------------------------------------------

// Test streams
// =================================================================================================

// {{{ ITestOutStream

interface ITestOutStream {
  function reset();

  function startSubtest();
  function endSubtest();

  function writeHeader();
  function writeFooter();
  function writePlan($_num_of_tests_);
  function writeSkipAll($_reason_);
  function writeTestCaseResult(TestCaseResult $_test_, $_number_);
  function writeAlteredTestCaseResult(AlteredTestCaseResult $_test_, $_number_);
  function writeBailOut($_reason_);
  function writeComment($_comment_);
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ ITestErrStream

interface ITestErrStream {
  function reset();

  function startSubtest();
  function endSubtest();

  function write($_value_);
}

// }}} ---------------------------------------------------------------------------------------------

// Test producer
// =================================================================================================

// {{{ TestProducerInterrupt

class TestProducerInterrupt extends Narvalo\Exception { }

// }}} ---------------------------------------------------------------------------------------------
// {{{ SkipAllTestProducerInterrupt

class SkipAllTestProducerInterrupt extends TestProducerInterrupt { }

// }}} ---------------------------------------------------------------------------------------------
// {{{ BailOutTestProducerInterrupt

class BailOutTestProducerInterrupt extends TestProducerInterrupt { }

// }}} ---------------------------------------------------------------------------------------------

// {{{ TestProducer

class TestProducer {
  private
    /// Error stream.
    $_errStream,
    /// Out stream.
    $_outStream,
    /// Test set.
    $_set,
    /// Test workflow.
    $_workflow,
    /// Was the producer interrupted?
    $_interrupted        = \FALSE,
    $_bailedOut          = \FALSE,
    $_runtimeErrorsCount = 0,
    $_currentTag,
    $_tagStack           = array();

  function __construct(ITestOutStream $_outStream_, ITestErrStream $_errStream_) {
    $this->_outStream = $_outStream_;
    $this->_errStream = $_errStream_;
    // NB: Until we have a plan, we use a dynamic test set.
    $this->_set       = new _\DynamicTestResultSet();
    $this->_workflow  = new _\TestWorkflow();
  }

  // Properties
  // ----------

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

  function busy() {
    return $this->_workflow->running();
  }

  //

  final function register() {
    (new TestModule())->initialize($this);
  }

  final function startup() {
    $this->_addHeader();

    $this->startupCore_();
  }

  final function shutdown() {
    if (!$this->_interrupted) {
      $this->_endTestResultSet();
      $this->_addFooter();
    }

    $this->shutdownCore_();

    $result = $this->_createResult();

    $this->_reset();

    return $result;
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

  // Test methods
  // ------------

  function skipAll($_reason_) {
    $this->_set = new _\EmptyTestResultSet();
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
      throw new Narvalo\ArgumentException(
        'how_many',
        \sprintf('Number of tests must be a strictly positive integer. You gave it "%s".',
          $_how_many_));
    }
    $this->_set = new _\FixedSizeTestResultSet($_how_many_);
    $this->_addPlan($_how_many_);
  }

  function assert($_test_, $_description_) {
    $test = new TestCaseResult($_description_, \TRUE === $_test_);

    if ($this->_inTag()) {
      $test = $test->mark($this->_currentTag);
      $number = $this->_set->addAlteredTest($test);
      $this->_addAlteredTestCaseResult($test, $number);
    } else {
      $number = $this->_set->addTest($test);
      $this->_addTestCaseResult($test, $number);
    }

    return $test->passed();
  }

  function ditch($_how_many_, DitchTestDirective $_directive_) {
    if (!self::_IsStrictlyPositiveInteger($_how_many_)) {
      throw new Narvalo\ArgumentException(
        'how_many',
        \sprintf(
          'The number of ditched tests must be a strictly positive integer. You gave it "%s".',
          $_how_many_));
    }
    $test = (new TestCaseResult('', \TRUE))->ditch($_directive_);
    for ($i = 1; $i <= $_how_many_; $i++) {
      $number = $this->_set->addAlteredTest($test);
      $this->_addAlteredTestCaseResult($test, $number);
    }
  }

  function startTagging(MarkTestDirective $_directive_) {
    $this->_startTagging();
    if ($this->_inTag()) {
      // Keep the upper-level tag in memory.
      \array_push($this->_tagStack, $this->_currentTag);
    }
    $this->_currentTag = $_directive_;
  }

  function endTagging() {
    $this->_endTagging();
    $this->_currentTag = \array_pop($this->_tagStack);
  }

  function subtest(\Closure $_fun_, $_description_) {
    // FIXME: If the subtest exit, it will stop the whole test.
    // Switch to a new TestResultSet.
    $set = $this->_set;
    $this->_set = new _\DynamicTestResultSet();
    // Notify outputs.
    $this->_startSubtest();
    // Execute the subtests.
    $_fun_();
    //
    $this->_endTestResultSet();
    // Restore outputs.
    $this->_endSubtest();
    //
    $passed = $this->_set->passed();
    // Restore the orginal TestResultSet.
    $this->_set = $set;
    // Report the result to the parent producer.
    $this->assert($passed, $_description_);
  }

  function diagnose($_diag_) {
    if ($this->_inTag()) {
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

  protected function startupCore_() {
    ;
  }

  // Utilities
  // ---------

  private static function _IsStrictlyPositiveInteger($_value_) {
    return (int)$_value_ === $_value_ && $_value_ > 0;
  }

  private function _createResult() {
    $result = new TestSetResult();
    $result->passed             = $this->passed();
    $result->bailedOut          = $this->_bailedOut;
    $result->runtimeErrorsCount = $this->_runtimeErrorsCount;
    $result->failuresCount      = $this->getFailuresCount();
    $result->testsCount         = $this->getTestsCount();

    return $result;
  }

  private function _inTag() {
    return $this->_workflow->inTag();
  }

  private function _postPlan() {
    if ($this->_set instanceof _\DynamicTestResultSet
      && ($tests_count = $this->_set->getTestsCount()) > 0
    ) {
      // We actually run tests.
      $this->_addPlan($tests_count);
    }
  }

  private function _endTestResultSet() {
    // Print helpful messages if something went wrong.
    // NB: This must stay above _postPlan().
    $this->_set->close($this->_errStream);

    $this->_postPlan();
  }

  private function _reset() {
    $this->_set                = new _\DynamicTestResultSet();
    $this->_bailedOut          = \FALSE;
    $this->_tagStack           = array();
    $this->_runtimeErrorsCount = 0;
    $this->_interrupted        = \FALSE;
    $this->_errStream->reset();
    $this->_outStream->reset();
    $this->_workflow->reset();
  }

  // Producer interrupts
  // -------------------

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

  // Core methods
  // ------------

  private function _addHeader() {
    $this->_workflow->enterHeader();
    $this->_outStream->writeHeader();
  }

  private function _addFooter() {
    $this->_workflow->enterFooter();
    $this->_outStream->writeFooter();
  }

  private function _startSubtest() {
    $this->_workflow->startSubtest();
    $this->_outStream->startSubtest();
    $this->_errStream->startSubtest();
  }

  private function _endSubtest() {
    $this->_workflow->endSubtest();
    $this->_outStream->endSubtest();
    $this->_errStream->endSubtest();
  }

  private function _startTagging() {
    $this->_workflow->startTagging();
    //$this->_outStream->startTagging();
    //$this->_errStream->startTagging();
  }

  private function _endTagging() {
    $this->_workflow->endTagging();
    //$this->_outStream->endTagging();
    //$this->_errStream->endTagging();
  }

  private function _addPlan($_num_of_tests_) {
    $this->_workflow->enterPlan();
    $this->_outStream->writePlan($_num_of_tests_);
  }

  private function _addSkipAll($_reason_) {
    $this->_workflow->enterSkipAll();
    $this->_outStream->writeSkipAll($_reason_);
  }

  private function _addTestCaseResult(TestCaseResult $_test_, $_number_) {
    $this->_workflow->enterTestCaseResult();
    $this->_outStream->writeTestCaseResult($_test_, $_number_);
  }

  private function _addAlteredTestCaseResult(AlteredTestCaseResult $_test_, $_number_) {
    $this->_workflow->enterTestCaseResult();
    $this->_outStream->writeAlteredTestCaseResult($_test_, $_number_);
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

// }}} ---------------------------------------------------------------------------------------------

// Test modules
// =================================================================================================

// {{{ TestModule

/// NB: you can create as many derived class as you wish, they will all share the same producer.
class TestModule {
  private static $_SharedProducer;
  private $_producer;

  function __construct() {
    // All modules share the same producer.
    $this->_producer =& self::$_SharedProducer;
  }

  function getProducer() {
    if (\NULL === $this->_producer) {
      throw new Narvalo\InvalidOperationException(
        'Looks like you forgot to initialize '.__CLASS__.' with a TestProducer.');
    }
    return $this->_producer;
  }

  function initialize(TestProducer $_producer_) {
    if (!$this->canInitialize()) {
      throw new Narvalo\InvalidOperationException(
        'You can not initialize '.__CLASS__.' while a TestProducer is still running.');
    }
    $this->_producer = $_producer_;
  }

  function canInitialize() {
    return \NULL === $this->_producer || !$this->_producer->busy();
  }
}

// }}} ---------------------------------------------------------------------------------------------

// #################################################################################################

namespace Narvalo\Test\Framework\Internal;

use \Narvalo;
use \Narvalo\Test\Framework;

// Test result sets
// =================================================================================================

// {{{ AbstractTestResultSet

abstract class AbstractTestResultSet {
  private
    /// Number of failed tests.
    $_failuresCount = 0,
    /// List of tests.
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

  abstract function close(Framework\ITestErrStream $_errStream_);

  abstract function passed();

  function addTest(Framework\TestCaseResult $_test_) {
    if (!$_test_->passed()) {
      $this->_failuresCount++;
    }
    $number = $this->getTestsCount();
    $this->_tests[$number] = $_test_;
    return 1 + $number;
  }

  function addAlteredTest(Framework\AlteredTestCaseResult $_test_) {
    if (!$_test_->passed()) {
      $this->_failuresCount++;
    }
    $number = $this->getTestsCount();
    $this->_tests[$number] = $_test_;
    return 1 + $number;
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ EmptyTestResultSet

final class EmptyTestResultSet extends AbstractTestResultSet {
  function __construct() {
    ;
  }

  function passed() {
    return \TRUE;
  }

  function close(Framework\ITestErrStream $_errStream_) {
    ;
  }

  final function addTest(Framework\TestCaseResult $_test_) {
    return 0;
  }

  final function addAlteredTest(Framework\AlteredTestCaseResult $_test_) {
    return 0;
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ DynamicTestResultSet

final class DynamicTestResultSet extends AbstractTestResultSet {
  function __construct() {
    ;
  }

  function close(Framework\ITestErrStream $_errStream_) {
    if (($tests_count = $this->getTestsCount()) > 0) {
      // We actually run tests.
      if (($failures_count = $this->getFailuresCount()) > 0) {
        // There are failures.
        $s = $failures_count > 1 ? 's' : '';
        $_errStream_->write(
          \sprintf('Looks like you failed %s test%s of %s run.',
            $failures_count, $s, $tests_count));
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

// }}} ---------------------------------------------------------------------------------------------
// {{{ FixedSizeTestResultSet

final class FixedSizeTestResultSet extends AbstractTestResultSet {
  /// Number of expected tests.
  private $_length;

  function __construct($_length_) {
    $this->_length = $_length_;
  }

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

  function close(Framework\ITestErrStream $_errStream_) {
    if (($tests_count = $this->getTestsCount()) > 0) {
      // We actually run tests.
      $extras_count = $this->getExtrasCount();
      if ($extras_count != 0) {
        // Count missmatch.
        $s = $this->_length > 1 ? 's' : '';
        $_errStream_->write(
          \sprintf('Looks like you planned %s test%s but ran %s.',
            $this->_length, $s, $tests_count));
      }
      if (($failures_count = $this->getFailuresCount()) > 0) {
        // There are failures.
        $s = $failures_count > 1 ? 's' : '';
        $qualifier = 0 == $extras_count ? '' : ' run';
        $_errStream_->write(
          \sprintf('Looks like you failed %s test%s of %s%s.',
            $failures_count, $s, $tests_count, $qualifier));
      }
    } else {
      // No tests run.
      $_errStream_->write('No tests run!');
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Test workflow
// =================================================================================================

// {{{ TestWorkflowException

class TestWorkflowException extends Narvalo\Exception { }

// }}} ---------------------------------------------------------------------------------------------

// {{{ TestWorkflow

final class TestWorkflow {
  const
    Start            = 0,
    Header           = 1,
    StaticPlanDecl   = 2,
    DynamicPlanTests = 3,
    // Valid end states.
    DynamicPlanDecl  = 4,
    StaticPlanTests  = 5,
    SkipAll          = 6,
    BailOut          = 7,
    End              = 8;

  private
    $_disposed     = \FALSE,
    $_state        = self::Start,
    $_subStates    = array(),
    $_subtestLevel = 0,
    $_tagLevel     = 0;

  function __destruct() {
    $this->dispose_(\TRUE);
  }

  function running() {
    // TODO: Check this.
    return self::Start !== $this->_state && self::End !== $this->_state;
  }

  function inTag() {
    return $this->_tagLevel > 0;
  }

  function inSubtest() {
    return $this->_subtestLevel > 0;
  }

  function close() {
    $this->dispose_(\FALSE);
  }

  function reset() {
    $this->_state        = self::Start;
    $this->_subStates    = array();
    $this->_subtestLevel = 0;
    $this->_tagLevel    = 0;
  }

  function enterHeader() {
    if (self::Start === $this->_state) {
      // Valid states.

      $this->_state = self::Header;
    } else {
      // Invalid states.

      throw new TestWorkflowException(
        \sprintf('The header must come first. Invalid workflow state: "%s".', $this->_state));
    }
  }

  function enterFooter() {
    // Check workflow's state.
    switch ($this->_state) {
      // Valid states.

    case self::BailOut:
    case self::DynamicPlanDecl:
    case self::StaticPlanTests:
    case self::SkipAll:
      // XXX
    case self::Header:
      break;

      // Invalid states.

    case self::End:
      throw new TestWorkflowException('Can not enter footer. Workflow already ended.');
      //    case self::Header:
      //      throw new TestWorkflowException('The workflow will end prematurely.');
    default:
      throw new TestWorkflowException(
        \sprintf('Can not enter footer. The workflow will end in an invalid state: "%s".',
          $this->_state));
    }
    // Check subtests' level.
    if (0 !== $this->_subtestLevel) {
      throw new TestWorkflowException(
        \sprintf('There is still "%s" opened subtest in the workflow.', $this->_subtestLevel));
    }
    // Check tag level.
    if (0 !== $this->_tagLevel) {
      throw new TestWorkflowException(
        \sprintf('There is still "%s" opened tag in the workflow.', $this->_tagLevel));
    }
    $this->_state = self::End;
  }

  function startSubtest() {
    switch ($this->_state) {
      // Valid states.

    case self::Header:
    case self::DynamicPlanTests:
    case self::StaticPlanDecl:
    case self::StaticPlanTests:
      break;

      // Invalid states.

    case self::Start:
      throw new TestWorkflowException('Unable to start a subtest: missing header.');
    case self::End:
      throw new TestWorkflowException('Unable to start a subtest: workflow ended.');
    case self::DynamicPlanDecl:
      throw new TestWorkflowException(
        'Unable to start a subtest: you already end your tests with a plan.');
    case self::SkipAll:
      throw new TestWorkflowException(
        'You can not start a subtest and skip all tests at the same time.');
    case self::BailOut:
      throw new TestWorkflowException('You can not start a subtest after bailing out.');
    default:
      throw new TestWorkflowException(\sprintf('Invalid workflow state: "%s".', $this->_state));
    }
    // FIXME: Reset tag level?
    \array_push($this->_subStates, $this->_state);
    $this->_state = self::Header;
    return ++$this->_subtestLevel;
  }

  /// \return void
  function endSubtest() {
    // FIXME: Valid states.
    if (0 === $this->_subtestLevel) {
      throw new TestWorkflowException('You can not end a subtest if you did not start one before.');
    }
    $this->_state = \array_pop($this->_subStates);
    $this->_subtestLevel--;
  }

  function startTagging() {
    switch ($this->_state) {
      // Valid states.

    case self::Header:
    case self::DynamicPlanTests:
    case self::StaticPlanDecl:
    case self::StaticPlanTests:
      break;

      // Invalid states.

    case self::Start:
      throw new TestWorkflowException('Unable to start a tag: missing header.');
    case self::End:
      throw new TestWorkflowException('Unable to start a tag: workflow ended.');
    case self::DynamicPlanDecl:
      throw new TestWorkflowException(
        'Unable to start a tag: you already end your tests with a plan.');
    case self::SkipAll:
      throw new TestWorkflowException(
        'You can not start a tag and skip all tests at the same time.');
    case self::BailOut:
      throw new TestWorkflowException('You can not start a tag after bailing out.');
    default:
      throw new TestWorkflowException(\sprintf('Invalid workflow state: "%s".', $this->_state));
    }
    return ++$this->_tagLevel;
  }

  function endTagging() {
    // FIXME: Valid states.
    if (0 === $this->_tagLevel) {
      throw new TestWorkflowException('You can not end a tag if you did not start one before.');
    }
    $this->_tagLevel--;
  }

  function enterPlan() {
    switch ($this->_state) {
      // Valid states.

    case self::Header:
      // Static plan.
      $this->_state = self::StaticPlanDecl;
      break;
    case self::DynamicPlanTests:
      // Dynamic plan.
      $this->_state = self::DynamicPlanDecl;
      break;

      // Invalid states.

    case self::Start:
      throw new TestWorkflowException('You can not plan: missing header.');
    case self::End:
      throw new TestWorkflowException('You can not plan: workflow already ended.');
    case self::DynamicPlanDecl:
    case self::StaticPlanDecl:
    case self::StaticPlanTests:
      throw new TestWorkflowException(
        \sprintf('You can not plan twice. Invalid workflow state: "%s".', $this->_state));
    case self::SkipAll:
      throw new TestWorkflowException('You can not plan and skip all tests at the same time.');
    case self::BailOut:
      throw new TestWorkflowException('You can not plan after bailing out.');
    default:
      throw new TestWorkflowException(\sprintf('Invalid workflow state: "%s".', $this->_state));
    }
  }

  function enterSkipAll() {
    switch ($this->_state) {
      // Valid states.

    case self::Header:
      // Skip All plan.
      $this->_state = self::SkipAll;
      break;

      // Invalid states.

    case self::Start:
      throw new TestWorkflowException('Unable to skip all tests: missing header.');
    case self::End:
      throw new TestWorkflowException('Unable to skip all tests: workflow already closed.');
    case self::DynamicPlanTests:
      throw new TestWorkflowException('Unable to skip all tests: you already run at least one test.');
    case self::DynamicPlanDecl:
    case self::StaticPlanDecl:
    case self::StaticPlanTests:
      throw new TestWorkflowException(
        \sprintf('Unable to skip all tests: you already made a plan. Invalid workflow state: "%s".',
          $this->_state));
    case self::SkipAll:
      throw new TestWorkflowException('You already asked to skip all tests.');
    case self::BailOut:
      throw new TestWorkflowException('You can not skip all tests after bailing out.');
    default:
      throw new TestWorkflowException(
        \sprintf('Invalid workflow state: "%s".', $this->_state));
    }
  }

  function enterTestCaseResult() {
    switch ($this->_state) {
      // Valid states.

    case self::Header:
      // Dynamic plan. First test.
      $this->_state = self::DynamicPlanTests;
      break;
    case self::DynamicPlanTests:
      // Dynamic plan. Later test.
      break;
    case self::StaticPlanDecl:
      // Static plan. First test.
      $this->_state = self::StaticPlanTests;
      break;
    case self::StaticPlanTests:
      // Static plan. Later test.
      break;

      // Invalid states.

    case self::Start:
      throw new TestWorkflowException('Unable to register a test: missing header.');
    case self::End:
      throw new TestWorkflowException('Unable to register a test: workflow already ended.');
    case self::DynamicPlanDecl:
      throw new TestWorkflowException(
        'Unable to register a test: you already end your tests with a plan.');
    case self::SkipAll:
      throw new TestWorkflowException('You can not register a test if you asked to skip all tests.');
    case self::BailOut:
      throw new TestWorkflowException('You can not register a test after bailing out.');
    default:
      throw new TestWorkflowException(\sprintf('Invalid workflow state: "%s".', $this->_state));
    }
  }

  function enterBailOut() {
    switch ($this->_state) {
      // Valid states.

    case self::Header:
    case self::DynamicPlanTests:
    case self::DynamicPlanDecl:
    case self::StaticPlanDecl:
    case self::StaticPlanTests:
    case self::SkipAll:
      $this->_state = self::BailOut;
      break;

      // Invalid states.

    case self::Start:
      throw new TestWorkflowException('You can not bail out: missing header.');
    case self::End:
      throw new TestWorkflowException('You can not bail out: workflow already ended.');
    case self::BailOut:
      throw new TestWorkflowException('You can not bail out twice.');
    default:
      throw new TestWorkflowException(\sprintf('Invalid workflow state: "%s".', $this->_state));
    }
  }

  function enterComment() {
    // This method does not change the current state.
    switch ($this->_state) {
      // Invalid states.

    case self::Start:
      throw new TestWorkflowException('You can not write a comment: missing header.');
    case self::End:
      throw new TestWorkflowException('You can not write a comment: workflow already ended.');

      // Valid states.

    default:
      break;
    }
  }

  function enterError() {
    // This method does not change the current state.
    switch ($this->_state) {
      // Invalid states.

    case self::Start:
      throw new TestWorkflowException('You can not write an error: missing header.');
    case self::End:
      throw new TestWorkflowException('You can not write an error: workflow already closed.');

      // Valid states.

    default:
      break;
    }
  }

  protected function dispose_($_disposing_) {
    if ($this->_disposed) {
      return;
    }
    // Check workflow's state.
    switch ($this->_state) {
      // Valid states.

    case self::Start:
    case self::End:
      // XXX reset() or no test at all.
      break;

      // Invalid states.

    default:
      Narvalo\Failure::ThrowOrReportInDispose(
        new TestWorkflowException(\sprintf(
          'The workflow will end in an invalid state: "%s".', $this->_state)),
          $_disposing_);
    }
    $this->_disposed = \TRUE;
  }
}

// }}} ---------------------------------------------------------------------------------------------

// EOF
