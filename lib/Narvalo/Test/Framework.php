<?php

namespace Narvalo\Test\Framework;

/// \return boolean
function is_strictly_positive_integer($_value_) {
  return (int)$_value_ === $_value_ && $_value_ > 0;
}

class FileStreamException extends \Exception { }

class TestWorkflowException extends \Exception { }

class TestModuleException extends \Exception { }

class NormalTestProducerInterrupt extends \Exception { }

class FatalTestProducerInterrupt extends \Exception { }

interface ITestCase {
  /// The test's description
  /// \return string
  function getDescription();

  /// TRUE if the test passed, FALSE otherwise
  /// \return boolean
  function passed();
}

interface OutStream {
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

interface ErrStream {
  function close();
  function reset();
  function canWrite();

  function startSubTest();
  function endSubTest();

  function write($_value_);
}

class TestCase implements ITestCase {
  protected
    $description,
    $passed;

  function __construct($_description_, $_passed_) {
    $this->description = empty($_description_) ? 'unamed test' : $_description_;
    $this->passed      = $_passed_;
  }

  function getDescription() {
    return $this->description;
  }

  function passed() {
    return $this->passed;
  }
}

abstract class AbstractSpecialTestCase implements ITestCase {
  protected $reason;

  protected function __construct() {
    ;
  }

  function reason() {
    return $this->reason;
  }
}

class SkipTestCase extends AbstractSpecialTestCase {
  function __construct($_reason_) {
    $this->reason = $_reason_;
  }

  function getDescription() {
    return '';
  }

  function passed() {
    return \TRUE;
  }
}

class TodoTestCase extends AbstractSpecialTestCase {
  protected $inner;

  function __construct(TestCase $_inner_, $_reason_) {
    $this->inner  = $_inner_;
    $this->reason = $_reason_;
  }

  function getDescription() {
    return $this->inner->getDescription();
  }

  function passed() {
    return $this->inner->passed();
  }
}

class FileStream {
  private static $_EndOfLine = "\n";
  private
    $_handle,
    $_indent   = '',
    $_opened = \FALSE;

  function __construct($_path_) {
    // Open the handle
    $handle = fopen($_path_, 'w');
    if (\FALSE === $handle) {
      throw new FileStreamException("Unable to open '{$_path_}' for writing");
    }
    $this->_opened = \TRUE;
    $this->_handle = $handle;
  }

  function __destruct() {
    $this->cleanup(\FALSE);
  }

  function dispose() {
    $this->cleanup(\TRUE);
  }

  function close() {
    $this->cleanup(\TRUE);
  }

  /// \param $_disposing_ TRUE if called explicitly, FALSE if called
  ///     from the GC.
  protected function cleanup($_disposing_) {
    if (!$this->_opened) {
      return;
    }
    if (\TRUE === fclose($this->_handle)) {
      $this->_opened = \FALSE;
    }
  }

  static function endOfLine() {
    return self::$_EndOfLine;
  }

  function opened() {
    // XXX
    return $this->_opened;
  }

  function reset() {
    $this->_indent = '';
  }

  function canWrite() {
    // XXX
    return $this->_opened && 0 === fwrite($this->_handle, '');
  }

  final protected function rawWrite($_value_) {
    return fwrite($this->_handle, $_value_);
  }

  protected function write($_value_) {
    return fwrite($this->_handle, $this->_indent . $_value_);
  }

  protected function writeLine($_value_) {
    return fwrite($this->_handle, $this->_indent . $_value_ . self::$_EndOfLine);
  }

  protected function indent() {
    $this->_indent = '    ' . $this->_indent;
  }

  protected function unindent() {
    $this->_indent = substr($this->_indent, 4);
  }

  protected function formatLine($_prefix_, $_value_) {
    return $_prefix_ . preg_replace(CRLF_REGEX, '', $value) . self::$_EndOfLine;
  }

  protected function formatMultiLine($_prefix_, $_value_) {
    $prefix = self:: $_EndOfLine . $this->_indent . $_prefix_;
    $value = preg_replace(TRAILING_CRLF_REGEX, '', $_value_);
    return $_prefix_ . preg_replace(MULTILINE_CRLF_REGEX, $prefix, $value) . self::$_EndOfLine;
  }
}

class NullOutStream implements OutStream {
  function close() {
    ;
  }

  function reset() {
    ;
  }

  function canWrite() {
    return \TRUE;
  }

  function startSubTest() {
    ;
  }

  function endSubTest() {
    ;
  }

  function writeHeader() {
    ;
  }

  function writeFooter() {
    ;
  }

  function writePlan($_num_of_tests_) {
    ;
  }

  function writeSkipAll($_reason_) {
    ;
  }

  function writeTestCase(TestCase $_test_, $_number_) {
    ;
  }

  function writeTodoTestCase(TodoTestCase $_test_, $_number_) {
    ;
  }

  function writeSkipTestCase(SkipTestCase $_test_, $_number_) {
    ;
  }

  function writeBailOut($_reason_) {
    ;
  }

  function writeComment($_comment_) {
    ;
  }
}

class NullErrStream implements ErrStream {
  function close() {
    ;
  }

  function reset() {
    ;
  }

  function canWrite() {
    return \TRUE;
  }

  function startSubTest() {
    ;
  }

  function endSubTest() {
    ;
  }

  function write($_value_) {
    ;
  }
}

abstract class AbstractTestSet {
  protected
    /// Number of failed tests
    $failuresCount  = 0,
    /// List of tests
    $tests          = array();

  protected function __construct() {
    ;
  }

  function getTestsCount() {
    return \count($this->tests);
  }

  function getFailuresCount() {
    return $this->failuresCount;
  }

  abstract function close(ErrStream $_stream_);
  ///
  abstract function passed();

  function addTest(ITestCase $_test_) {
    if (!$_test_->passed()) {
      $this->failuresCount++;
    }
    $number = $this->getTestsCount();
    $this->tests[$number] = $_test_;
    return 1 + $number;
  }
}

class EmptyTestSet extends AbstractTestSet {
  function __construct() {
    ;
  }

  function passed() {
    return \TRUE;
  }

  function close(ErrStream $_stream_) {
    ;
  }

  final function addTest(ITestCase $_test_) {
    return 0;
  }
}

class DynamicTestSet extends AbstractTestSet {
  function __construct() {
    ;
  }

  function close(ErrStream $_stream_) {
    //
    if (($tests_count = $this->getTestsCount()) > 0) {
      // We actually run tests.
      if ($this->failuresCount > 0) {
        // There are failures.
        $s = $this->failuresCount > 1 ? 's' : '';
        $_stream_->write("Looks like you failed {$this->failuresCount} test{$s} "
          . "of {$tests_count} run.");
      }
      $_stream_->write('No plan!');
    }
    else {
      // No tests run.
      $_stream_->write('No plan. No tests run!');
    }
  }

  function passed() {
    // We actually run tests and they all passed.
    return 0 === $this->failuresCount && $this->getTestsCount() != 0;
  }
}

class FixedSizeTestSet extends AbstractTestSet {
  /// Number of expected tests
  protected $length;

  function __construct($_length_) {
    $this->length = $_length_;
  }

  /// \return integer
  function getLength() {
    return $this->length;
  }

  function getExtrasCount() {
    return $this->getTestsCount() - $this->length;
  }

  function passed() {
    // We actually run tests, they all passed and there are no extras tests.
    return 0 === $this->failuresCount && $this->getTestsCount() != 0
      && 0 === $this->getExtrasCount();
  }

  function close(ErrStream $_stream_) {
    //
    if (($tests_count = $this->getTestsCount()) > 0) {
      // We actually run tests.
      $extras_count = $this->getExtrasCount();
      if ($extras_count != 0) {
        // Count missmatch.
        $s = $this->length > 1 ? 's' : '';
        $_stream_->write("Looks like you planned {$this->length} test{$s} "
          . "but ran {$tests_count}.");
      }
      if ($this->failuresCount > 0) {
        // There are failures.
        $s = $this->failuresCount > 1 ? 's' : '';
        $qualifier = 0 == $extras_count ? '' : ' run';
        $_stream_->write("Looks like you failed {$this->failuresCount} test{$s} "
          . "of {$tests_count}{$qualifier}.");
      }
    }
    else {
      // No tests run.
      $_stream_->write('No tests run!');
    }
  }
}

class TestWorkflow {
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
    $_hasDisposed  = \FALSE,
    $_state        = self::START,
    $_subStates    = array(),
    $_subTestLevel = 0,
    // TODO stack level
    $_todoLevel    = 0;

  function __destruct() {
    $this->cleanup(\FALSE);
  }

  function dispose() {
    $this->cleanup(\TRUE);
  }

  protected function cleanup($_disposing_) {
    if ($this->_hasDisposed) {
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
      trigger_error('The workflow will end in an invalid state: ' . $this->_state,
        E_USER_WARNING);
    }
    $this->_hasDisposed = \TRUE;
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
    }
    else {
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
      break;
      // Invalid state.
    case self::END:
      throw new TestWorkflowException('Workflow already ended');
    case self::HEADER:
      throw new TestWorkflowException('The workflow will end prematurely');
    default:
      throw new TestWorkflowException('The workflow will end in an invalid state: ' . $this->_state);
    }
    // Check subtests' level
    if (0 !== $this->_subTestLevel) {
      throw new TestWorkflowException('There is still an opened subtest in the workflow: ' . $this->_subTestLevel);
    }
    // Check TODO' level
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
    // FIXME reset TODO level?
    array_push($this->_subStates, $this->_state);
    $this->_state = self::HEADER;
    return ++$this->_subTestLevel;
  }

  /// \return void
  function endSubTest() {
    // FIXME: valid states
    if (0 === $this->_subTestLevel) {
      throw new TestWorkflowException('You can not end a subtest if you did not start one before');
    }
    $this->_state = array_pop($this->_subStates);
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

class TestProducer {
  protected
    /// Error stream
    $errStream,
    /// Out stream
    $outStream;
  private
    /// TAP set
    $_set,
    // TODO stack level
    $_todoLevel     = 0,
    // TODO reason
    $_todoReason    = '',
    // TODO stack
    $_todoStack     = array(),
    //
    $_workflow;

  function __construct(OutStream $_outStream_, ErrStream $_errStream_) {
    $this->outStream = $_outStream_;
    $this->errStream = $_errStream_;
    // NB: Until we make a plan, we use a dynamic TAP set.
    $this->_set      = new DynamicTestSet();
    $this->_workflow = new TestWorkflow();
  }

  function getOutStream() {
    return $this->outStream;
  }

  function getErrStream() {
    return $this->errStream;
  }

  function getFailuresCount() {
    return $this->_set->getFailuresCount();
  }

  function passed() {
    return $this->_set->passed();
  }

  function inTodo() {
    return $this->_workflow->inTodo();
  }

  function reset() {
    $this->errStream->reset();
    $this->outStream->reset();
    $this->_set        = new DynamicTestSet();
    $this->_todoLevel  = 0;
    $this->_todoReason = '';
    $this->_todoStack  = array();
    $this->_workflow   = new TestWorkflow();
  }

  function startup() {
    $this->_addHeader();
  }

  function skipAll($_reason_) {
    $this->_set = new EmptyTestSet();
    $this->_addSkipAll($_reason_);
    $this->_addFooter();
    $this->NormalInterrupt();
  }

  function bailOut($_reason_) {
    $this->_addBailOut($_reason_);
    $this->_addFooter();
    $this->FatalInterrupt();
  }

  function shutdown() {
    $this->_postPlan();
    $this->_endTestSet();
    $this->_addFooter();
  }

  function plan($_how_many_) {
    if (!is_strictly_positive_integer($_how_many_)) {
      // Invalid argument exception
      $this->bailOut('Number of tests must be a strictly positive integer. '
        . "You gave it '$_how_many_'.");
    }
    $this->_set = new FixedSizeTestSet($_how_many_);
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
    $test = new TestCase($_description_, \TRUE === $_test_);
    if ($this->inTodo()) {
      $test = new TodoTestCase($test, $this->_todoReason);
      $number = $this->_set->addTest($test);
      $this->_addTodoTestCase($test, $number);
    }
    else {
      $number = $this->_set->addTest($test);
      $this->_addTestCase($test, $number);
    }

    if (!$test->passed()) {
      // if the test failed, display the source of the prob
      $what = $this->inTodo() ? '(TODO) test' : 'test';
      $caller = $this->FindCaller();
      $description = $test->getDescription();
      if (empty($description)) {
        $diag = <<<EOL
Failed $what at {$caller['file']} line {$caller['line']}.
EOL;
      }
      else {
        $diag = <<<EOL
Failed $what '$description'
at {$caller['file']} line {$caller['line']}.
EOL;
      }
      $this->Diagnose($diag);
    }

    return $test->passed();
  }

  function skip($_how_many_, $_reason_) {
    if (!is_strictly_positive_integer($_how_many_)) {
      $errmsg = 'The number of skipped tests must be a strictly positive integer';
      if ($this->_set instanceof FixedSizeTestSet) {
        $this->bailOut($errmsg);
      }
      else {
        $this->Warn($errmsg); return;
      }
    }
    if ($this->inTodo()) {
      $this->bailOut('You can not interlace a SKIP directive with a TODO block');
    }
    $test = new SkipTestCase($_reason_);
    for ($i = 1; $i <= $_how_many_; $i++) {
      $number = $this->_set->addTest($test);
      $this->_addSkipTestCase($test, $number);
    }
  }

  function startTodo($_reason_) {
    if ($this->inTodo()) {
      // Keep the upper-level TODO in memory
      array_push($this->_todoStack, $this->_todoReason);
    }
    $this->_todoReason = $_reason_;
    $this->_startTodo();
  }

  function endTodo() {
        /*
        if (!$this->inTodo()) {
            $this->bailOut('You can not end a TODO block if you did not start one before');
        }
         */
    $this->_endTodo();
    $this->_todoReason = $this->inTodo() ? array_pop($this->_todoStack) : '';
  }

  // FIXME: if the subtest exit at any time it will exit the whole test.
  function subTest(TestModule $_m_, $_code_, $_description_) {
    // Switch to a new TestSet.
    $set = $this->_set;
    $this->_set = new DynamicTestSet();
    // Notify outputs.
    $this->_startSubTest();
    // Execute the tests.
    $_code_($_m_);
    //
    $this->_postPlan();
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

  function Diagnose($_diag_) {
    if ($this->inTodo()) {
      $this->_addComment($_diag_);
    }
    else {
      $this->_addError($_diag_);
    }
  }

  function Note($_note_) {
    $this->_addComment($_note_);
  }

  function Warn($_errmsg_) {
    $this->_addError($_errmsg_);
  }

  function FindCaller() {
    $calltree = debug_backtrace();
    $file = $calltree['2']['file'];
    $line = $calltree['2']['line'];
    return array('file' => $file,  'line' => $line);
  }

  protected function FatalInterrupt() {
    // THIS IS BAD! but I do not see any other way to do it.
    throw new FatalTestProducerInterrupt();
  }

  protected function NormalInterrupt() {
    // THIS IS BAD! but I do not see any other way to do it.
    throw new NormalTestProducerInterrupt();
  }

  protected function _postPlan() {
    if ($this->_set instanceof DynamicTestSet
      && ($tests_count = $this->_set->getTestsCount()) > 0
    ) {
      // We actually run tests.
      $this->_addPlan($tests_count);
    }
  }

  protected function _endTestSet() {
    // Print helpful messages if something went wrong.
    $this->_set->close($this->errStream);
  }

  private function _addHeader() {
    $this->_workflow->enterHeader();
    $this->outStream->writeHeader();
  }

  private function _addFooter() {
    $this->_workflow->enterFooter();
    $this->outStream->writeFooter();
  }
  /// \return integer
  private function _startSubTest() {
    $this->_workflow->startSubTest();
    $this->outStream->startSubTest();
    $this->errStream->startSubTest();
  }
  /// \return void
  private function _endSubTest() {
    $this->_workflow->endSubTest();
    $this->outStream->endSubTest();
    $this->errStream->endSubTest();
  }
  /// \return integer
  private function _startTodo() {
    $this->_workflow->startTodo();
    //$this->outStream->startTodo();
    //$this->errStream->startTodo();
  }
  /// \return void
  private function _endTodo() {
    $this->_workflow->endTodo();
    //$this->outStream->endTodo();
    //$this->errStream->endTodo();
  }
  private function _addPlan($_num_of_tests_) {
    $this->_workflow->enterPlan();
    $this->outStream->writePlan($_num_of_tests_);
  }
  private function _addSkipAll($_reason_) {
    $this->_workflow->enterSkipAll();
    $this->outStream->writeSkipAll($_reason_);
  }
  private function _addTestCase(TestCase $_test_, $_number_) {
    $this->_workflow->enterTestCase();
    $this->outStream->writeTestCase($_test_, $_number_);
  }
  private function _addTodoTestCase(TodoTestCase $_test_, $_number_) {
    $this->_workflow->enterTestCase();
    $this->outStream->writeTodoTestCase($_test_, $_number_);
  }
  private function _addSkipTestCase(SkipTestCase $_test_, $_number_) {
    $this->_workflow->enterTestCase();
    $this->outStream->writeSkipTestCase($_test_, $_number_);
  }
  private function _addBailOut($_reason_) {
    $this->_workflow->enterBailOut();
    $this->outStream->writeBailOut($_reason_);
  }
  private function _addComment($_comment_) {
    $this->_workflow->enterComment();
    $this->outStream->writeComment($_comment_);
  }
  private function _addError($_errmsg_) {
    $this->_workflow->enterError();
    $this->errStream->write($_errmsg_);
  }
}


/// NB: TestModule is a Borg: you can create as many instances of this
/// class as you wish and they will all share the same state.
/// Do not confuse this with the Singleton pattern, where only one
/// instance exists at any given time.
class TestModule {
  // All modules share the same producer.
  private static $_SharedProducer;
  private $_producer;

  /// Class initialization.
  /// Ensure any static property has a value.
  private static function Construct() {
    if (\NULL === self::$_SharedProducer) {
      throw new TestModuleException('First, you must initialize ' . __CLASS__);
            /*
            self::$_SharedProducer
                = new TestProducer(new \Narvalo\Test\Tap\StandardTapOutStream(\TRUE),
                    new \Narvalo\Test\Tap\StandardTapErrStream());
             */
    }
  }
  function __construct() {
    self::Construct();
    $this->_producer =& self::$_SharedProducer;
  }

  static function SharedProducer() {
    self::Construct();
    return self::$_SharedProducer;
  }

  function getProducer() {
    return $this->_producer;
  }

  static function Initialize(TestProducer $_producer_) {
    if (self::$_SharedProducer !== \NULL) {
      throw new TestModuleException(__CLASS__ . ' already initialized');
    }
    self::$_SharedProducer = $_producer_;
  }
  static function reset(TestProducer $_producer_ = \NULL) {
    self::$_SharedProducer = $_producer_;
  }
}

class TestRunnerHelper {
  private
    // Errors list.
    $_errors     = array(),
      // PHP display_errors on/off.
      //$_phpDisplayErrors,
      // PHP error reporting level.
      $_phpErrorReporting;

  function __construct() {
    ;
  }

  function getErrorsCount() {
    return \count($this->_errors);
  }

  function OverrideErrorHandler() {
    // One way or another, we want to see all errors.
    //$this->_phpDisplayErrors  = ini_get('display_errors');
    $this->_phpErrorReporting = \ini_get('error_reporting');
    //ini_set('display_errors', 'Off');
    \error_reporting(E_ALL | E_STRICT);
    // Beware we can not catch all errors.
    // See: http://php.net/manual/en/function.set-error-handler.php
    // The following error types cannot be handled with a user defined
    // function: E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING,
    // E_COMPILE_ERROR, E_COMPILE_WARNING, and most of E_STRICT raised in
    // the file where set_error_handler() is called.
    $errors =& $this->_errors;
    \set_error_handler(
      function ($errno , $errstr, $errfile, $errline, $errcontext) use (&$errors) {
        \array_push($errors, "Error at {$errfile} line {$errline}.\n$errstr");
      }
    );
  }
  function restoreErrorHandler() {
    \restore_error_handler();
    // Restore PHP settings
    //ini_set('display_errors', $this->_phpDisplayErrors);
    \error_reporting($this->_phpErrorReporting);
  }
  function PushError($_error_) {
    \array_push($this->_errors, $_error_);
  }
  function writeErrors(ErrStream $_errStream_) {
    $count = $this->getErrorsCount();
    if ($count > 0) {
      for ($i = 0; $i < $count; $i++) {
        $_errStream_->write($this->_errors[$i]);
      }
      trigger_error('There are hidden errors', E_USER_WARNING);
    }
    return $count;
  }
}

/// NB: Only one TestRunner may exist at a given time.
final class TestRunner {
  const
    CODE_SUCCESS = 0,
    CODE_FATAL   = 255;

  private static $_Instance;
  private $_helper;

  private function __construct() {
    $this->_helper = new TestRunnerHelper();
  }
  final private function __clone() {
    ;
  }

  /// Singleton method.
  static function UniqInstance() {
    if (\NULL === self::$_Instance) {
      self::$_Instance = new self();
    }
    return self::$_Instance;
  }

  function RunTest($_test_) {
    // Override default error handler.
    $this->_helper->OverrideErrorHandler();
    // Run the test specification.
    try {
      $loaded = include_once $_test_;
    }
    catch (NormalTestProducerInterrupt $e) {
      ;
    }
    catch (FatalTestProducerInterrupt $e) {
      ;
    }
    catch (\Exception $e) {
      $this->_helper->PushError('Unexpected error: ' . $e->getMessage());
      goto TERMINATE;
    }

    if (\FALSE === $loaded) {
    }

    TERMINATE:
      $producer = TestModule::SharedProducer();
    // Restore default error handler.
    $this->_helper->restoreErrorHandler();
    $errors_count = $this->_helper->writeErrors($producer->getErrStream());
    //
    if ($errors_count > 0) {
      $exit_code = self::CODE_FATAL;
    }
    else {
      $exit_code = $this->ExitCode($producer);
    }
    //
    $this->Terminate($exit_code);
  }

  protected function Terminate($_code_) {
    exit($_code_);
  }
  protected function ExitCode($_producer_) {
    if ($_producer_->passed()) {
      // All tests passed.
      $code = self::CODE_SUCCESS;
    }
    else if (($count = $_producer_->getFailuresCount()) > 0) {
      // There are failed tests.
      $code = $count < self::CODE_FATAL ? $count : (self::CODE_FATAL - 1);
    }
    else {
      // Other kind of errors: extra tests, unattended interrupt.
      $code = self::CODE_FATAL;
    }
    return $code;
  }
}

final class TestHarness {
  private
    // Errors list.
    $_errorsCount = 0,
    // PHP display_errors on/off.
    //$_phpDisplayErrors,
    // PHP error reporting level.
    $_phpErrorReporting;

  function __construct() {
    ;
  }

  function RunTests($_tests_) {
    // Open streams.
    // Create a new test producer: no output at all.
    $producer = new TestProducer(new NullOutStream(), new NullErrStream());
    TestModule::Initialize($producer);
    // Override default error handler.
    $this->_OverrideErrorHandler();
    // Run the test suite.
    foreach ($_tests_ as $test) {
      try {
        // We turn off error reporting otherwise we will have duplicate errors.
        // We eval the code otherwise the include call may abort the whole script.
        //$errlevel = ini_get('error_reporting');
        //error_reporting(0);
        $exit_code = include_once $test;
        //error_reporting($errlevel);
      }
      catch (TestProducerException $e) {
        // XXX
        $exit_code = $e->getCode();
      }
      catch (\Exception $e) {
        exit('Unexpected error: ' . $e->getMessage());
      }

      if (TestProducer::CODE_SUCCESS === $exit_code) {
        $status= 'OK';
      }
      else if ($exit_code > TestProducer::CODE_SUCCESS
        && $exit_code <= TestProducer::CODE_FATAL
      ) {
        $status = 'KO';
      }
      else if (\FALSE === $exit_code) {
        $status = 'NOT FOUND';
      }
      else {
        $status = 'UNKNOWN';
      }

      //
      if ($this->_errorsCount > 0) {
        // There are hidden errors. See diagnostics above
        $status .= ' DUBIOUS';
      }
      $this->_errorsCount = 0;

      if (($dotlen = 40 - \strlen($test)) > 0) {
        $statusline = $test . \str_repeat('.', $dotlen) . $status;
      }
      else {
        $statusline = $test . '...'. $status;
      }
      echo $statusline, "\n";

      // reset.
      $producer->reset();
    }
    // Restore default error handler.
    $this->_restoreErrorHandler();
  }

  private function _OverrideErrorHandler() {
    // One way or another, we want to see all errors.
    //$this->_phpDisplayErrors  = ini_get('display_errors');
    $this->_phpErrorReporting = \ini_get('error_reporting');
    //ini_set('display_errors', 'Off');
    \error_reporting(E_ALL | E_STRICT);
    // Beware we can not catch all errors.
    // See: http://php.net/manual/en/function.set-error-handler.php
    // The following error types cannot be handled with a user defined
    // function: E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING,
    // E_COMPILE_ERROR, E_COMPILE_WARNING, and most of E_STRICT raised in
    // the file where set_error_handler() is called.
    $errorsCount =& $this->_errorsCount;
    \set_error_handler(
      function ($errno , $errstr, $errfile, $errline, $errcontext) use (&$errorsCount) {
        $errorsCount++;
      }
    );
  }

  private function _restoreErrorHandler() {
    \restore_error_handler();
    // Restore PHP settings
    \error_reporting($this->_phpErrorReporting);
  }
}

// #################################################################################################

//\note IMPORTANT
//
//DO NOT import any other file, the testing library MUST STAY self contained.
//
//\todo
//
//\li Add a verbose mode for TapRunner
//\li check all constructors for validity
//\li reset states
//\li return values for methods
//\li can TAP normal and error streams use the same FH?
//\li flush on handles to ensure correct ordering
//\li test the test
//\li in a subtest, we should unindent in bailout
//\li how to catch exceptions so that they do not garble the output
//\li Test::Harness, Test::Differences, Test::Deeper, Test::Class, Test::Most
//\li doc: code, usage, diff with Test::More, error reporting
//
//\par ERROR REPORTING
//
//Several ways to report an error:
//\li throws an Exception for any internal error and for fatal error
//    where we can not use TestProducer::bailOut()
//\li trigger_error()
//    * \c E_USER_ERROR for any fatal error during GC
//    * \c E_USER_WARNING for any non-fatal error where we can not use \c TestProducer::Warn()
//\li \c TestProducer::bailOut() for remaining fatal errors
//\li \c TestProducer::Warn() for remaining non-fatal errors

// EOF
