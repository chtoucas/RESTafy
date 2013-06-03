<?php

namespace Narvalo\Test\Runner;

require_once 'NarvaloBundle.php';
require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/SetsBundle.php';

use \Narvalo;
use \Narvalo\Test\Framework;
use \Narvalo\Test\Sets;
use \Narvalo\Test\Runner\Internal as _;

// Test runner
// =================================================================================================

// {{{ TestRunner

class TestRunner {
  private
    $_errorCatcher,
    $_producer;

  function __construct(Framework\TestProducer $_producer_) {
    $this->_producer     = $_producer_;
    $this->_errorCatcher = new _\RuntimeErrorCatcher($_producer_);
  }

  function run(Sets\ITestSet $_set_) {
    Narvalo\Guard::NotNull($_set_, 'set');

    $this->_producer->start();
    $this->_errorCatcher->start();

    try {
      $_set_->run();
    } catch (Framework\TestProducerInterrupt $e) {
      ;
    } catch (\Exception $e) {
      $this->_producer->bailOutOnException($e);
    }

    $this->_errorCatcher->stop();
    return $this->_producer->stop();
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Test harness
// =================================================================================================

// {{{ ITestHarnessWriter

interface ITestHarnessWriter {
  function writeResult($_name_, Framework\TestSetResult $_result_);
  function writeSummary(TestHarnessSummary $_summary_);
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ TestHarnessSummary

class TestHarnessSummary {
  public
    $passed           = \TRUE,
    $setsCount        = 0,
    $failedSetsCount  = 0,
    $testsCount       = 0,
    $failedTestsCount = 0;
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ TestHarness

class TestHarness {
  private
    $_writer,
    $_runner;

  function __construct(
    ITestHarnessWriter       $_writer_,
    Framework\ITestOutWriter $_outWriter_ = \NULL,
    Framework\ITestOutWriter $_errWriter_ = \NULL
  ) {
    $this->_writer = $_writer_;

    $producer = new Framework\TestProducer(
      $_outWriter_ ?: new _\NoopTestOutWriter(),
      $_errWriter_ ?: new _\NoopTestErrWriter()
    );
    $producer->register();

    $this->_runner = new TestRunner($producer);
  }

  function executeSets(array $_sets_) {
    return $this->execute_(new \ArrayIterator($_sets_));
  }

  function executeFiles(array $_paths_) {
    return $this->execute_(new Sets\FileTestSetIterator($_paths_));
  }

  function scanDirectoryAndExecute($_path_, $_file_ext_ = 'phpt') {
    return $this->execute_(
      Sets\InDirectoryFileTestSetIterator::FromPath($_path_, $_file_ext_));
  }

  protected function execute_(\Iterator $_it_) {
    $summary = new TestHarnessSummary();

    foreach ($_it_ as $set) {
      $result = $this->_runner->run($set);

      $this->_writer->writeResult($set->getName(), $result);

      if ($summary->passed && !$result->passed) {
        $summary->passed = \FALSE;
      }

      $summary->setsCount++;
      $summary->failedSetsCount  += $result->passed ? 0 : 1;
      $summary->testsCount       += $result->testsCount;
      $summary->failedTestsCount += $result->failuresCount;
    }

    $this->_writer->writeSummary($summary);
    return $summary;
  }
}

// }}} ---------------------------------------------------------------------------------------------

// #################################################################################################

namespace Narvalo\Test\Runner\Internal;

use \Narvalo;
use \Narvalo\Test\Framework;

// Utilities
// =================================================================================================

// {{{ RuntimeErrorCatcher

final class RuntimeErrorCatcher extends Narvalo\StartStopWorkflow_ {
  private $_producer;

  function __construct(Framework\TestProducer $_producer_) {
    $this->_producer = $_producer_;
  }

  function __destruct() {
    // If not already done, we restore the original error handler.
    // NB: No need to call the parent __destruct().
    $this->stop();
  }

  protected function startCore_() {
    // We override the error handler, but beware, one can not catch all type of errors.
    // Cf. http://php.net/manual/en/function.set-error-handler.php
    // The following error types cannot be handled with a user defined function:
    // E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING,
    // E_COMPILE_ERROR, E_COMPILE_WARNING, and most of E_STRICT raised in
    // the file where set_error_handler() is called.

    \set_error_handler(
      function($errno , $errstr, $errfile, $errline, $errcontext) {
        $this->_producer->captureRuntimeError(
          \sprintf('Error at %s line %s.%s%s', $errfile, $errline, \PHP_EOL, $errstr));
      }
    );
  }

  protected function stopCore_() {
    \restore_error_handler();
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Noop streams
// =================================================================================================

// {{{ NoopTestOutWriter

final class NoopTestOutWriter implements Framework\ITestOutWriter {
  function reset() {
    ;
  }

  function startSubtest() {
    ;
  }

  function endSubtest() {
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

  function writeTestCaseResult(Framework\TestCaseResult $_test_, $_number_) {
    ;
  }

  function writeAlteredTestCaseResult(Framework\AlteredTestCaseResult $_test_, $_number_) {
    ;
  }

  function writeBailOut($_reason_) {
    ;
  }

  function writeComment($_comment_) {
    ;
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ NoopTestErrWriter

final class NoopTestErrWriter implements Framework\ITestErrWriter {
  function reset() {
    ;
  }

  function startSubtest() {
    ;
  }

  function endSubtest() {
    ;
  }

  function write($_value_) {
    ;
  }
}

// }}} ---------------------------------------------------------------------------------------------

// EOF
