<?php

namespace Narvalo\Test\Runner;

require_once 'NarvaloBundle.php';
require_once 'Narvalo\Test\FrameworkBundle.php';
require_once 'Narvalo\Test\SuitesBundle.php';

use \Narvalo;
use \Narvalo\Test\Framework;
use \Narvalo\Test\Suites;
use \Narvalo\Test\Runner\Internal as _;

// Test runner.
// #################################################################################################

// {{{ TestRunner

class TestRunner {
  private
    $_errorCatcher,
    $_producer;

  function __construct(Framework\TestProducer $_producer_) {
    $this->_producer     = $_producer_;
    $this->_errorCatcher = new _\RuntimeErrorCatcher($_producer_);

    // FIXME: Find a better way to initialize TestKernel.
    Framework\TestKernel::Bootstrap($_producer_);
  }

  function run(Suites\TestSuite $_suite_) {
    Narvalo\Guard::NotNull($_suite_, 'suite');

    $this->_producer->startup();
    $this->_errorCatcher->start();

    try {
      $_suite_->setup();
      $_suite_->execute();
    } catch (Framework\TestProducerInterrupt $e) {
      ;
    } catch (\Exception $e) {
      $this->_producer->bailOutOnException($e);
    }

    $_suite_->teardown();

    $this->_errorCatcher->stop();
    return $this->_producer->shutdown();
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Test harness.
// #################################################################################################

// {{{ TestHarnessSummary

class TestHarnessSummary {
  public
    $passed           = \FALSE,
    $suitesCount      = 0,
    $failedSuitesCount = 0,
    $testsCount       = 0,
    $failuresCount    = 0;
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ TestHarnessStream

interface TestHarnessStream {
  function close();
  function canWrite();

  function writeResult($_name_, Framework\TestResult $_result_);
  function writeSummary(TestHarnessSummary $_summary_);
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ TestHarness

class TestHarness {
  private
    $_stream,
    $_runner,
    $_summary;

  function __construct(TestHarnessStream $_stream_, Framework\TestErrStream $_errStream_ = \NULL) {
    $this->_stream = $_stream_;
    $this->_summary = new TestHarnessSummary();

    $producer = new Framework\TestProducer(
      new _\NoopTestOutStream(),
      $_errStream_ ?: new _\NoopTestErrStream());

    $this->_runner = new TestRunner($producer);
  }

  function scanDirectoryAndExecute($_directory_, $_filter_ = '/^.+\.phpt$/i') {
    $it = new \RegexIterator(
      new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($_directory_)),
      $_filter_,
      \RecursiveRegexIterator::GET_MATCH);

    foreach ($it as $path => $_) {
      $this->execute_(new Suites\FileTestSuite($path));
    }

    $summary = $this->_getSummary();
    $this->_stream->writeSummary($summary);

    $this->reset_();
    return $summary;
  }

  function executeTestFiles(array $_files_) {
    for ($i = 0, $count = \count($_files_); $i < $count; $i++) {
      $this->execute_(new Suites\FileTestSuite($_files_[$i]));
    }

    $summary = $this->_getSummary();
    $this->_stream->writeSummary($summary);

    $this->reset_();
    return $summary;
  }

  protected function reset_() {
    $this->_summary = new TestHarnessSummary();
  }

  protected function execute_(Suites\TestSuite $_suite_) {
    $this->_summary->suitesCount++;

    $result = $this->_runner->run($_suite_);

    $this->_stream->writeResult($_suite_->getName(), $result);

    if ($this->_summary->passed && !$result->passed) {
      $this->_summary->passed = \FALSE;
    }

    $this->_summary->failedSuitesCount += $result->passed ? 0 : 1;
    $this->_summary->testsCount        += $result->testsCount;
    $this->_summary->failuresCount     += $result->failuresCount;
  }

  private function _getSummary() {
    return clone $this->_summary;
  }
}

// }}} ---------------------------------------------------------------------------------------------

namespace Narvalo\Test\Runner\Internal;

use \Narvalo\Test\Framework;

// Utilities.
// #################################################################################################

// {{{ RuntimeErrorCatcher

final class RuntimeErrorCatcher {
  private
    // PHP display_errors on/off.
    //$_phpDisplayErrors,
    // PHP display_startup_errors on/off.
    //$_phpDisplayStartupErrors,
    // PHP error reporting level.
    //$_phpErrorReporting,
    $_producer;

  function __construct(Framework\TestProducer $_producer_) {
    $this->_producer = $_producer_;
  }

  function start() {
    // One way or another, we want to see all errors.
    //$this->_phpDisplayStartupErrors = \ini_get('display_startup_errors');
    //$this->_phpDisplayErrors = \ini_get('display_errors');
    //$this->_phpErrorReporting = \ini_get('error_reporting');

    //\ini_set('ignore_repeated_source', '1');
    //\ini_set('ignore_repeated_errors', '1');
    //\ini_set('report_memleaks', '1');
    //\ini_set('html_errors', '0');
    //\ini_set('display_startup_errors', '0');
    //\ini_set('display_errors', '1');
    //\error_reporting(\E_ALL);

    // Beware we can not catch all errors.
    // See: http://php.net/manual/en/function.set-error-handler.php
    // The following error types cannot be handled with a user defined
    // function: E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING,
    // E_COMPILE_ERROR, E_COMPILE_WARNING, and most of E_STRICT raised in
    // the file where set_error_handler() is called.
    $producer = $this->_producer;

    \set_error_handler(
      function($errno , $errstr, $errfile, $errline, $errcontext) use ($producer) {
        $producer->captureRuntimeError("Error at {$errfile} line {$errline}.\n$errstr");
      }
    );
  }

  function stop() {
    // Restore error handler.
    \restore_error_handler();
    // Restore PHP settings.
    //\ini_set('display_startup_errors', $this->_phpDisplayStartupErrors);
    //\ini_set('display_errors', $this->_phpDisplayErrors);
    //\error_reporting($this->_phpErrorReporting);
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Noop streams.
// #################################################################################################

// {{{ NoopTestOutStream

final class NoopTestOutStream implements Framework\TestOutStream {
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

  function writeTestCase(Framework\TestCase $_test_, $_number_) {
    ;
  }

  function writeTodoTestCase(Framework\TodoTestCase $_test_, $_number_) {
    ;
  }

  function writeSkipTestCase(Framework\SkipTestCase $_test_, $_number_) {
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
// {{{ NoopTestErrStream

final class NoopTestErrStream implements Framework\TestErrStream {
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

// }}} ---------------------------------------------------------------------------------------------

// EOF
