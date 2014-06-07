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

// Test harness
// =================================================================================================

interface ITestHarnessWriter {
  function writeResult($_name_, Framework\TestSetResult $_result_);

  function writeSummary(TestHarnessSummary $_summary_);
}

class TestHarnessSummary {
  private
    $_passed           = \TRUE,
    $_setsCount        = 0,
    $_failedSetsCount  = 0,
    $_dubiousSetsCount = 0,
    $_testsCount       = 0,
    $_failedTestsCount = 0;

  function passed() {
    return $this->_passed;
  }

  function getSetsCount() {
    return $this->_setsCount;
  }

  function getFailedSetsCount() {
    return $this->_failedSetsCount;
  }

  function getDubiousSetsCount() {
    return $this->_dubiousSetsCount;
  }

  function getTestsCount() {
    return $this->_testsCount;
  }

  function getFailedTestsCount() {
    return $this->_failedTestsCount;
  }

  function addTestSetResult(Framework\TestSetResult $_result_) {
    $failed = $_result_->bailedOut() || !$_result_->passed();

    if ($this->_passed && $failed) {
      $this->_passed = \FALSE;
    }

    $this->_setsCount++;
    $this->_failedSetsCount  += $failed ? 1 : 0;
    $this->_dubiousSetsCount += $_result_->getRuntimeErrorsCount() > 0 ? 1 : 0;
    $this->_testsCount       += $_result_->getTestsCount();
    $this->_failedTestsCount += $_result_->getFailuresCount();
  }
}

class TestHarness {
  private
    $_writer,
    $_runner;

  function __construct(ITestHarnessWriter $_writer_, TestRunner $_runner_) {
    $this->_writer = $_writer_;
    $this->_runner = $_runner_;
  }

  function executeSets(array $_sets_) {
    $this->execute_(new \ArrayIterator($_sets_));
  }

  function executeFiles(array $_paths_) {
    $this->execute_(new Sets\FileTestSetIterator($_paths_));
  }

  function scanDirectoryAndExecute($_path_, $_file_ext_ = 'phpt') {
    $this->execute_(Sets\InDirectoryFileTestSetIterator::FromPath($_path_, $_file_ext_));
  }

  protected function execute_(\Iterator $_it_) {
    $summary = new TestHarnessSummary();

    foreach ($_it_ as $set) {
      $result = $this->_runner->run($set);

      $this->_writer->writeResult($set->getName(), $result);

      $summary->addTestSetResult($result);
    }

    $this->_writer->writeSummary($summary);
  }
}

// #################################################################################################

namespace Narvalo\Test\Runner\Internal;

use \Narvalo;
use \Narvalo\Test\Framework;

// Utilities
// =================================================================================================

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

// EOF
