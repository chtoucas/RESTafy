<?php

namespace Narvalo\Test\Framework;

require_once 'NarvaloBundle.php';

use \Narvalo;
use \Narvalo\Test\Framework\Internal as _;

// Test directives
// =================================================================================================

abstract class TestDirectiveBase {
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

  function alter(TestCaseResult $_test_) {
    return new AlteredTestCaseResult($_test_, $this);
  }
}

class SkipTestDirective extends TestDirectiveBase {
  function __construct($_reason_, $_name_) {
    parent::__construct($_reason_, $_name_);
  }

  final function apply(TestCaseResult $_test_) {
    return \TRUE;
  }
}

class TagTestDirective extends TestDirectiveBase {
  function __construct($_reason_, $_name_) {
    parent::__construct($_reason_, $_name_);
  }

  final function apply(TestCaseResult $_test_) {
    return $_test_->passed();
  }
}

// Test results
// =================================================================================================

class TestCaseResult {
  private
    $_description,
    $_passed;

  function __construct($_description_, $_passed_) {
    $this->_description = $_description_;
    $this->_passed      = $_passed_;
  }

  // The test's description.
  function getDescription() {
    return $this->_description;
  }

  // TRUE if the test passed, FALSE otherwise.
  function passed() {
    return $this->_passed;
  }
}

class AlteredTestCaseResult {
  private
    $_directive,
    $_inner;

  function __construct(TestCaseResult $_inner_, TestDirectiveBase $_directive_) {
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

final class TestSetResult {
  private
    $_set,
    $_bailedOut,
    $_runtimeErrorsCount;

  function __construct(_\TestResultSetBase $_set_, $_bailedOut_, $_runtimeErrorsCount_) {
    $this->_set                = $_set_;
    $this->_bailedOut          = $_bailedOut_;
    $this->_runtimeErrorsCount = $_runtimeErrorsCount_;
  }

  function passed() {
    return $this->_set->passed();
  }

  function bailedOut() {
    return $this->_bailedOut;
  }

  function getFailuresCount() {
    return $this->_set->getFailuresCount();
  }

  function getTestsCount() {
    return $this->_set->getTestsCount();
  }

  function getRuntimeErrorsCount() {
    return $this->_runtimeErrorsCount;
  }
}

// Test streams
// =================================================================================================

interface ITestOutWriter {
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

interface ITestErrWriter {
  function reset();

  function startSubtest();

  function endSubtest();

  function write($_value_);
}

final class NoopTestOutWriter implements ITestOutWriter {
  function reset() { }

  function startSubtest() { }

  function endSubtest() { }

  function writeHeader() { }

  function writeFooter() { }

  function writePlan($_num_of_tests_) { }

  function writeSkipAll($_reason_) { }

  function writeTestCaseResult(TestCaseResult $_test_, $_number_) { }

  function writeAlteredTestCaseResult(AlteredTestCaseResult $_test_, $_number_) { }

  function writeBailOut($_reason_) { }

  function writeComment($_comment_) { }
}

final class NoopTestErrWriter implements ITestErrWriter {
  function reset() { }

  function startSubtest() { }

  function endSubtest() { }

  function write($_value_) { }
}

// Test engine
// =================================================================================================

class TestEngine {
  private
    // Out stream.
    $_outWriter,
    // Error stream.
    $_errWriter,
    // Test workflow.
    $_workflow;

  function __construct(ITestOutWriter $_outWriter_, ITestErrWriter $_errWriter_) {
    $this->_outWriter = $_outWriter_;
    $this->_errWriter = $_errWriter_;
    $this->_workflow  = new _\TestWorkflow();
  }

  function running() {
    return $this->_workflow->running();
  }

  function getTagger() {
    return $this->_workflow->getTagger();
  }

  function reset() {
    $this->_workflow->stop();
    $this->_errWriter->reset();
    $this->_outWriter->reset();
  }

  function start() {
    $this->_workflow->start();
  }

  function header() {
    $this->_workflow->header();
    $this->_outWriter->writeHeader();
  }

  function footer() {
    $this->_workflow->footer();
    $this->_outWriter->writeFooter();
  }

  function stop() {
    $this->_workflow->stop();
  }

  function bailOut($_reason_) {
    $this->_workflow->bailOut();
    $this->_outWriter->writeBailOut($_reason_);
  }

  function bailOutOnException(\Exception $_e_) {
    // TODO: Write the exception trace to the error stream.
    $this->_workflow->bailOut();
    $this->_outWriter->writeBailOut($_e_->getMessage());
  }

  function skipAll($_reason_) {
    $this->_workflow->skipAll();
    $this->_outWriter->writeSkipAll($_reason_);
  }

  function plan($_num_of_tests_) {
    $this->_workflow->plan();
    $this->_outWriter->writePlan($_num_of_tests_);
  }

  function startSubtest() {
    $this->_workflow->startSubtest();
    $this->_outWriter->startSubtest();
    $this->_errWriter->startSubtest();
  }

  function endSubtest() {
    $this->_workflow->endSubtest();
    $this->_outWriter->endSubtest();
    $this->_errWriter->endSubtest();
  }

  function startTagging(TagTestDirective $_tagger_) {
    $this->_workflow->startTagging($_tagger_);
  }

  function endTagging($_tagger_) {
    $this->_workflow->endTagging($_tagger_);
  }

  function addTestCaseResult(TestCaseResult $_test_, $_number_) {
    $this->_workflow->testCaseResult();
    $this->_outWriter->writeTestCaseResult($_test_, $_number_);
  }

  function addAlteredTestCaseResult(AlteredTestCaseResult $_test_, $_number_) {
    $this->_workflow->testCaseResult();
    $this->_outWriter->writeAlteredTestCaseResult($_test_, $_number_);
  }

  function addComment($_comment_) {
    $this->_workflow->comment();
    $this->_outWriter->writeComment($_comment_);
  }

  function addError($_errmsg_) {
    $this->_workflow->error();
    $this->_errWriter->write($_errmsg_);
  }
}

// Test producer
// =================================================================================================

class TestProducerInterrupt extends Narvalo\Exception { }

class SkipAllTestProducerInterrupt extends TestProducerInterrupt { }

class BailOutTestProducerInterrupt extends TestProducerInterrupt { }

class TestProducer {
  private
    // Test engine.
    $_engine,
    // Test set.
    $_set,
    $_bailedOut          = \FALSE,
    $_runtimeErrorsCount = 0;

  function __construct(TestEngine $_engine_) {
    $this->_engine = $_engine_;
    // NB: Until we make a plan, we use a dynamic test set.
    $this->_set    = new _\DynamicTestResultSet();
  }

  function running() {
    return $this->_engine->running();
  }

  // Core methods
  // ------------

  final function register() {
    (new TestModule())->initialize($this);
  }

  final function start() {
    $this->_engine->start();
    $this->_engine->header();
  }

  final function stop() {
    $this->_set->close($this->_engine);
    $this->_engine->footer();
    $this->_engine->stop();

    $result = new TestSetResult($this->_set, $this->_bailedOut, $this->_runtimeErrorsCount);

    $this->_reset();

    return $result;
  }

  function captureRuntimeError($_error_) {
    $this->_runtimeErrorsCount++;
    $this->_engine->addError($_error_);
  }

  // Test interrupts
  // ---------------

  function skipAll($_reason_) {
    $this->_set = new _\EmptyTestResultSet();
    $this->_engine->skipAll($_reason_);
    // THIS IS BAD but I do not see any other simple way to stop the execution here.
    throw new SkipAllTestProducerInterrupt();
  }

  function bailOut($_reason_) {
    $this->_bailedOut = \TRUE;
    $this->_engine->bailOut($_reason_);
    // THIS IS BAD but I do not see any other simple way to stop the execution here.
    throw new BailOutTestProducerInterrupt();
  }

  // Same as bailOut() but does not throw an exception. Only useful in a catch block.
  function bailOutOnException(\Exception $_e_) {
    $this->_bailedOut = \TRUE;
    $this->_engine->bailOutOnException($_e_);
  }

  // Test methods
  // ------------

  function plan($_how_many_) {
    if (!self::_IsStrictlyPositiveInteger($_how_many_)) {
      throw new Narvalo\ArgumentException(
        'how_many',
        \sprintf('Number of tests must be a strictly positive integer. You gave it "%s".',
        $_how_many_));
    }

    $this->_set = new _\FixedSizeTestResultSet($_how_many_);
    $this->_engine->plan($_how_many_);
  }

  function assert($_test_, $_description_) {
    $test = new TestCaseResult($_description_, \TRUE === $_test_);

    if (\NULL !== ($tagger = $this->_engine->getTagger())) {
      $test = $tagger->alter($test);
      $num = $this->_set->addAlteredTest($test);
      $this->_engine->addAlteredTestCaseResult($test, $num);
    } else {
      $num = $this->_set->addTest($test);
      $this->_engine->addTestCaseResult($test, $num);
    }

    return $test->passed();
  }

  function skip($_how_many_, SkipTestDirective $_directive_) {
    if (!self::_IsStrictlyPositiveInteger($_how_many_)) {
      throw new Narvalo\ArgumentException(
        'how_many',
        \sprintf(
          'The number of skipped tests must be a strictly positive integer. You gave it "%s".',
          $_how_many_));
    }

    $test = $_directive_->alter(new TestCaseResult('', \TRUE));

    for ($i = 1; $i <= $_how_many_; $i++) {
      $num = $this->_set->addAlteredTest($test);
      $this->_engine->addAlteredTestCaseResult($test, $num);
    }
  }

  function startTagging(TagTestDirective $_tagger_) {
    $this->_engine->startTagging($_tagger_);
  }

  function endTagging(TagTestDirective $_tagger_) {
    $this->_engine->endTagging($_tagger_);
  }

  function subtest(\Closure $_fun_, $_description_) {
    // Store the current state.
    $set = $this->_set;
    $runtimeErrorsCount = $this->_runtimeErrorsCount;

    // Execute the subtest.
    $this->_engine->startSubtest();
    $this->_set = new _\DynamicTestResultSet();
    $this->_runtimeErrorsCount = 0;

    try {
      $_fun_();
    } catch (TestProducerInterrupt $e) {
      // FIXME: Explain this.
    } catch (\Exception $e) {
      $this->bailOutOnException($e);
    }

    $this->_set->close($this->_engine);
    $this->_engine->endSubtest();

    $result = new TestSetResult($this->_set, $this->_bailedOut, $this->_runtimeErrorsCount);

    // Restore the original state.
    $this->_set = $set;
    $this->_bailedOut = \FALSE;
    $this->_runtimeErrorsCount += $runtimeErrorsCount;

    // Report the result.
    $this->assert(!$result->bailedOut() && $result->passed(), $_description_);
  }

  function note($_note_) {
    $this->_engine->addComment($_note_);
  }

  function warn($_errmsg_) {
    $this->_engine->addError($_errmsg_);
  }

  // Private methods
  // ---------------

  private static function _IsStrictlyPositiveInteger($_value_) {
    return (int)$_value_ === $_value_ && $_value_ > 0;
  }

  private function _reset() {
    $this->_set                = new _\DynamicTestResultSet();
    $this->_bailedOut          = \FALSE;
    $this->_runtimeErrorsCount = 0;
    $this->_engine->reset();
  }
}

// Test modules
// =================================================================================================

// NB: you can create as many derived class as you wish, they will always share the same producer.
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
    return \NULL === $this->_producer || !$this->_producer->running();
  }
}

// Utilities
// =================================================================================================

abstract class StartStopWorkflowBase {
  private $_running = \FALSE;

  protected function __construct() { }

  function __destruct() {
    if ($this->_running) {
      // REVIEW
      Narvalo\Log::Warning(\sprintf(
        '%s forcefully stopped. You either forgot to call stop() or your script exited abnormally.',
        Narvalo\Type::Of($this)));
    }
  }

  function running() {
    return $this->_running;
  }

  final function start() {
    if ($this->_running) {
      throw new Narvalo\InvalidOperationException(
        \sprintf('You can not start an already running %s.', Narvalo\Type::Of($this)));
    }

    $this->startCore_();

    $this->_running = \TRUE;
  }

  final function stop() {
    if ($this->_running) {
      $this->stopCore_();
      $this->_running = \FALSE;
    }
  }

  abstract protected function startCore_();

  abstract protected function stopCore_();

  protected function throwIfStopped_() {
    if (!$this->_running) {
      throw new Narvalo\InvalidOperationException(
        \sprintf('%s stopped. You forget to call start()?', Narvalo\Type::Of($this)));
    }
  }
}

// #################################################################################################

namespace Narvalo\Test\Framework\Internal;

use \Narvalo;
use \Narvalo\Test\Framework;

// Test result sets
// =================================================================================================

abstract class TestResultSetBase {
  private
    // TRUE if the set is closed.
    $_closed        = \FALSE,
    // Number of failed tests.
    $_failuresCount = 0,
    // List of tests.
    $_tests         = array();

  protected function __construct() { }

  function getTestsCount() {
    return \count($this->_tests);
  }

  function getFailuresCount() {
    return $this->_failuresCount;
  }

  abstract protected function passedCore_();

  abstract protected function closeCore_(Framework\TestEngine $_engine_);

  function passed() {
    if (!$this->_closed) {
      // NB: This is not taken care of by the workflow.
      throw new Narvalo\InvalidOperationException(
        'Before getting the test status, you must close it.');
    }

    return $this->passedCore_();
  }

  function close(Framework\TestEngine $_engine_) {
    if ($this->_closed) {
      return;
    }

    $this->closeCore_($_engine_);
    $this->_closed = \TRUE;
  }

  function addTest(Framework\TestCaseResult $_test_) {
    if ($this->_closed) {
      // NB: This is not taken care of by the workflow.
      throw new Narvalo\InvalidOperationException('You can not add a test to a closed set.');
    }

    if (!$_test_->passed()) {
      $this->_failuresCount++;
    }

    $num = $this->getTestsCount();
    $this->_tests[$num] = $_test_;
    return 1 + $num;
  }

  function addAlteredTest(Framework\AlteredTestCaseResult $_test_) {
    if ($this->_closed) {
      // NB: This is not taken care of by the workflow.
      throw new Narvalo\InvalidOperationException('You can not add a test to a closed set.');
    }

    if (!$_test_->passed()) {
      $this->_failuresCount++;
    }

    $num = $this->getTestsCount();
    $this->_tests[$num] = $_test_;
    return 1 + $num;
  }
}

final class EmptyTestResultSet extends TestResultSetBase {
  function __construct() { }

  function addTest(Framework\TestCaseResult $_test_) {
    throw new Narvalo\NotSupportedException('You can not add a test to '.__CLASS__);
  }

  function addAlteredTest(Framework\AlteredTestCaseResult $_test_) {
    throw new Narvalo\NotSupportedException('You can not add an altered test to '.__CLASS__);
  }

  protected function passedCore_() {
    return \TRUE;
  }

  protected function closeCore_(Framework\TestEngine $_engine_) { }
}

final class DynamicTestResultSet extends TestResultSetBase {
  function __construct() { }

  protected function passedCore_() {
    // We actually run tests and they all passed.
    return 0 === $this->getFailuresCount() && $this->getTestsCount() != 0;
  }

  // Print helpful messages if something went wrong AND execute post plan logic.
  protected function closeCore_(Framework\TestEngine $_engine_) {
    if (($tests_count = $this->getTestsCount()) > 0) {
      // We actually run tests.
      if (($failures_count = $this->getFailuresCount()) > 0) {
        // There are failures.
        $s = $failures_count > 1 ? 's' : '';
        $_engine_->addComment(\sprintf(
          'Looks like you failed %s test%s of %s run.', $failures_count, $s, $tests_count));
      }

      $_engine_->addComment('No plan!');

      // Post plan.
      $_engine_->plan($tests_count);
    } else {
      // No tests run.
      $_engine_->addComment('No plan. No tests run!');
    }
  }
}

final class FixedSizeTestResultSet extends TestResultSetBase {
  // Number of expected tests.
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

  protected function passedCore_() {
    // We actually run tests, they all passed and there are no extras tests.
    return 0 === $this->getFailuresCount()
      && 0 !== $this->getTestsCount()       // XXX: no test = failure?
      && 0 === $this->getExtrasCount();
  }

  // Print helpful messages if something went wrong.
  protected function closeCore_(Framework\TestEngine $_engine_) {
    if (($tests_count = $this->getTestsCount()) > 0) {
      // We actually run tests.
      $extras_count = $this->getExtrasCount();

      if (0 !== $extras_count) {
        // Count missmatch.
        $s = $this->_length > 1 ? 's' : '';
        $_engine_->addComment(\sprintf(
          'Looks like you planned %s test%s but ran %s.', $this->_length, $s, $tests_count));
      }

      if (($failures_count = $this->getFailuresCount()) > 0) {
        // There are failures.
        $s = $failures_count > 1 ? 's' : '';
        $qualifier = 0 == $extras_count ? '' : ' run';
        $_engine_->addComment(\sprintf(
          'Looks like you failed %s test%s of %s%s.',
          $failures_count, $s, $tests_count, $qualifier));
      }
    } else {
      // No tests run.
      $_engine_->addComment('No tests run!');
    }
  }
}

// Test workflow
// =================================================================================================

class TestWorkflowException extends Narvalo\Exception { }

final class TestWorkflow extends Framework\StartStopWorkflowBase {
  const
    START              = 0,
    HEADER             = 1,
    STATIC_PLAN_DECL   = 2,
    DYNAMIC_PLAN_TESTS = 3,

    // Valid end states.
    DYNAMIC_PLAN_DECL  = 4,
    STATIC_PLAN_TESTS  = 5,
    SKIP_ALL           = 6,
    BAIL_OUT           = 7,
    END                = 8;

  private
    $_state,
    $_subStates,
    $_subtestLevel,
    $_tagger,
    $_taggerStack;

  function __construct() { }

  function getTagger() {
    return $this->_tagger;
  }

  function header() {
    $this->throwIfStopped_();

    if (self::START === $this->_state) {
      // Valid states.

      $this->_state = self::HEADER;
    } else {
      // Invalid states.

      throw new TestWorkflowException(\sprintf(
        'The header must come first. Invalid workflow state: "%s".',
        self::_GetStateDisplayName($this->_state)));
    }
  }

  function footer() {
    $this->throwIfStopped_();

    // Check workflow's state.
    switch ($this->_state) {
      // Valid states.
      case self::BAIL_OUT:
      case self::SKIP_ALL:
        // XXX
        return;

      case self::DYNAMIC_PLAN_DECL:
      case self::STATIC_PLAN_TESTS:
      case self::HEADER:
        break;

      // Invalid states.
      case self::END:
        throw new TestWorkflowException('Can not enter footer. Workflow already ended.');
        //    case self::Header:
        //      throw new TestWorkflowException('The workflow will end prematurely.');
      default:
        throw new TestWorkflowException(\sprintf(
          'Can not enter footer. The workflow will end in an invalid state: "%s".',
          self::_GetStateDisplayName($this->_state)));
    }

    // Check subtests' level.
    if (0 !== $this->_subtestLevel) {
      throw new TestWorkflowException(\sprintf(
        'There is still %s opened subtest in the workflow: %s',
        $this->_subtestLevel,
        self::_GetStateDisplayName($this->_state)));
    }

    // Is any tag left opened?
    if (\NULL !== $this->_tagger) {
      throw new TestWorkflowException('There is still opened tag in the workflow.');
    }

    $this->_state = self::END;
  }

  function startSubtest() {
    $this->throwIfStopped_();

    switch ($this->_state) {
      // Valid states.
      case self::HEADER:
      case self::DYNAMIC_PLAN_TESTS:
      case self::STATIC_PLAN_DECL:
      case self::STATIC_PLAN_TESTS:
        break;

      // Invalid states.
      case self::START:
        throw new TestWorkflowException('Unable to start a subtest: missing header.');
      case self::END:
        throw new TestWorkflowException('Unable to start a subtest: workflow ended.');
      case self::DYNAMIC_PLAN_DECL:
        throw new TestWorkflowException(
          'Unable to start a subtest: you already end your tests with a plan.');
      case self::SKIP_ALL:
        throw new TestWorkflowException(
          'You can not start a subtest and skip all tests at the same time.');
      case self::BAIL_OUT:
        throw new TestWorkflowException('You can not start a subtest after bailing out.');
      default:
        throw new TestWorkflowException(\sprintf(
          'Invalid workflow state: "%s".', self::_GetStateDisplayName($this->_state)));
    }

    // FIXME: Reset tag stack?
    \array_push($this->_subStates, $this->_state);
    $this->_state = self::HEADER;
    $this->_subtestLevel++;
  }

  function endSubtest() {
    $this->throwIfStopped_();

    // FIXME: Valid states.
    if (0 === $this->_subtestLevel) {
      throw new TestWorkflowException('You can not end a subtest if you did not start one before.');
    }
    $this->_state = \array_pop($this->_subStates);
    $this->_subtestLevel--;
  }

  function startTagging(Framework\TagTestDirective $_tagger_) {
    $this->throwIfStopped_();

    switch ($this->_state) {
      // Valid states.
      case self::HEADER:
      case self::DYNAMIC_PLAN_TESTS:
      case self::STATIC_PLAN_DECL:
      case self::STATIC_PLAN_TESTS:
        break;

      // Invalid states.
      case self::START:
        throw new TestWorkflowException('Unable to start a tag: missing header.');
      case self::END:
        throw new TestWorkflowException('Unable to start a tag: workflow ended.');
      case self::DYNAMIC_PLAN_DECL:
        throw new TestWorkflowException(
          'Unable to start a tag: you already end your tests with a plan.');
      case self::SKIP_ALL:
        throw new TestWorkflowException(
          'You can not start a tag and skip all tests at the same time.');
      case self::BAIL_OUT:
        throw new TestWorkflowException('You can not start a tag after bailing out.');
      default:
        throw new TestWorkflowException(\sprintf(
          'Invalid workflow state: "%s".', self::_GetStateDisplayName($this->_state)));
    }

    // Keep the upper-level directive in memory.
    if (\NULL === $this->_tagger) {
      $this->_tagger = $_tagger_;
    } else {
      \array_push($this->_taggerStack, $_tagger_);
    }
  }

  function endTagging(Framework\TagTestDirective $_tagger_) {
    $this->throwIfStopped_();

    // FIXME: Valid states.
    if (\NULL === $this->_tagger) {
      throw new TestWorkflowException('You can not end a tag if you did not start one before.');
    } else if ($_tagger_ !== $this->_tagger) {
      throw new TestWorkflowException('You can not interlinked tagging directives.');
    }

    $this->_tagger = \array_pop($this->_taggerStack);
  }

  function plan() {
    $this->throwIfStopped_();

    switch ($this->_state) {
      // Valid states.
      case self::HEADER:
        // Static plan.
        $this->_state = self::STATIC_PLAN_DECL;
        break;
      case self::DYNAMIC_PLAN_TESTS:
        // Dynamic plan.
        $this->_state = self::DYNAMIC_PLAN_DECL;
        break;

      // Invalid states.
      case self::START:
        throw new TestWorkflowException('You can not plan: missing header.');
      case self::END:
        throw new TestWorkflowException('You can not plan: workflow already ended.');
      case self::DYNAMIC_PLAN_DECL:
      case self::STATIC_PLAN_DECL:
      case self::STATIC_PLAN_TESTS:
        throw new TestWorkflowException(\sprintf(
          'You can not plan twice. Invalid workflow state: "%s".',
          self::_GetStateDisplayName($this->_state)));
      case self::SKIP_ALL:
        throw new TestWorkflowException('You can not plan and skip all tests at the same time.');
      case self::BAIL_OUT:
        throw new TestWorkflowException('You can not plan after bailing out.');
      default:
        throw new TestWorkflowException(\sprintf(
          'Invalid workflow state: "%s".', self::_GetStateDisplayName($this->_state)));
    }
  }

  function skipAll() {
    $this->throwIfStopped_();

    switch ($this->_state) {
      // Valid states.
      case self::HEADER:
        // Skip All plan.
        $this->_state = self::SKIP_ALL;
        break;

      // Invalid states.
      case self::START:
        throw new TestWorkflowException('Unable to skip all tests: missing header.');
      case self::END:
        throw new TestWorkflowException('Unable to skip all tests: workflow already closed.');
      case self::DYNAMIC_PLAN_TESTS:
        throw new TestWorkflowException('Unable to skip all tests: you already run at least one test.');
      case self::DYNAMIC_PLAN_DECL:
      case self::STATIC_PLAN_DECL:
      case self::STATIC_PLAN_TESTS:
        throw new TestWorkflowException(\sprintf(
          'Unable to skip all tests: you already made a plan. Invalid workflow state: "%s".',
          self::_GetStateDisplayName($this->_state)));
      case self::SKIP_ALL:
        throw new TestWorkflowException('You already asked to skip all tests.');
      case self::BAIL_OUT:
        throw new TestWorkflowException('You can not skip all tests after bailing out.');
      default:
        throw new TestWorkflowException(\sprintf(
          'Invalid workflow state: "%s".', self::_GetStateDisplayName($this->_state)));
    }
  }

  function testCaseResult() {
    $this->throwIfStopped_();

    switch ($this->_state) {
      // Valid states.
      case self::HEADER:
        // Dynamic plan. First test.
        $this->_state = self::DYNAMIC_PLAN_TESTS;
        break;
      case self::DYNAMIC_PLAN_TESTS:
        // Dynamic plan. Later test.
        break;
      case self::STATIC_PLAN_DECL:
        // Static plan. First test.
        $this->_state = self::STATIC_PLAN_TESTS;
        break;
      case self::STATIC_PLAN_TESTS:
        // Static plan. Later test.
        break;

      // Invalid states.
      case self::START:
        throw new TestWorkflowException('Unable to register a test: missing header.');
      case self::END:
        throw new TestWorkflowException('Unable to register a test: workflow already ended.');
      case self::DYNAMIC_PLAN_DECL:
        throw new TestWorkflowException(
          'Unable to register a test: you already end your tests with a plan.');
      case self::SKIP_ALL:
        throw new TestWorkflowException('You can not register a test if you asked to skip all tests.');
      case self::BAIL_OUT:
        throw new TestWorkflowException('You can not register a test after bailing out.');
      default:
        throw new TestWorkflowException(\sprintf(
          'Invalid workflow state: "%s".', self::_GetStateDisplayName($this->_state)));
    }
  }

  function bailOut() {
    $this->throwIfStopped_();

    switch ($this->_state) {
      // Valid states.
      case self::HEADER:
      case self::DYNAMIC_PLAN_TESTS:
      case self::DYNAMIC_PLAN_DECL:
      case self::STATIC_PLAN_DECL:
      case self::STATIC_PLAN_TESTS:
      case self::SKIP_ALL:
        $this->_state = self::BAIL_OUT;
        break;

      // Invalid states.
      case self::START:
        throw new TestWorkflowException('You can not bail out: missing header.');
      case self::END:
        throw new TestWorkflowException('You can not bail out: workflow already ended.');
      case self::BAIL_OUT:
        throw new TestWorkflowException('You can not bail out twice.');
      default:
        throw new TestWorkflowException(\sprintf(
          'Invalid workflow state: "%s".', self::_GetStateDisplayName($this->_state)));
    }
  }

  function comment() {
    $this->throwIfStopped_();

    // This method does not change the current state.
    switch ($this->_state) {
      // Invalid states.
      case self::START:
        throw new TestWorkflowException('You can not write a comment: missing header.');
      case self::END:
        throw new TestWorkflowException('You can not write a comment: workflow already ended.');

      // Valid states.
      default:
        break;
    }
  }

  function error() {
    $this->throwIfStopped_();

    // This method does not change the current state.
    switch ($this->_state) {
      // Invalid states.
      case self::START:
        throw new TestWorkflowException('You can not write an error: missing header.');
      case self::END:
        throw new TestWorkflowException('You can not write an error: workflow already closed.');

      // Valid states.
      default:
        break;
    }
  }

  protected function startCore_() {
    $this->_state        = self::START;
    $this->_subStates    = array();
    $this->_subtestLevel = 0;
    $this->_tagger       = \NULL;
    $this->_taggerStack  = array();
  }

  protected function stopCore_() {
    // Check workflow's state.
    switch ($this->_state) {
      // Valid states.
      case self::START:
      case self::END:
      case self::BAIL_OUT:
      case self::SKIP_ALL:
        break;

      // Invalid states.
      default:
        throw new TestWorkflowException(\sprintf(
          'The workflow will end in an invalid state: "%s".',
          self::_GetStateDisplayName($this->_state)));
    }
  }

  // Private methods
  // ---------------

  private static function _GetStateDisplayName($_state_) {
    switch ($_state_) {
      case self::START:              return 'Start';
      case self::HEADER:             return 'Header';
      case self::STATIC_PLAN_DECL:   return 'StaticPlanDecl';
      case self::DYNAMIC_PLAN_TESTS: return 'DynamicPlanTests';
      case self::DYNAMIC_PLAN_DECL:  return 'DynamicPlanDecl';
      case self::STATIC_PLAN_TESTS:  return 'StaticPlanTests';
      case self::SKIP_ALL:           return 'SkipAll';
      case self::BAIL_OUT:           return 'BailOut';
      case self::END:                return 'End';
      default:
        throw new Narvalo\ArgumentException('state', 'Unknown state.');
    }
  }
}

// EOF
