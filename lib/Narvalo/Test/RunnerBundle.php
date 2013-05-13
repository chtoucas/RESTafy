<?php

namespace Narvalo\Test\Runner;

require_once 'NarvaloBundle.php';
require_once 'Narvalo\Test\FrameworkBundle.php';
require_once 'Narvalo\Test\SuitesBundle.php';

use \Narvalo;
use \Narvalo\Test\Framework;
use \Narvalo\Test\Suites;
use \Narvalo\Test\Runner\Internal as _;

// {{{ TestRunner

class TestRunner {
  private
    $_errorCatcher,
    $_producer;

  function __construct(Framework\TestProducer $_producer_) {
    $this->_producer     = $_producer_;
    $this->_errorCatcher = new _\RuntimeErrorCatcher($_producer_);

    // FIXME
    Framework\TestModulesKernel::Bootstrap($_producer_, \TRUE);
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

// }}} #############################################################################################

// {{{ TestHarnessOutStream

interface TestHarnessOutStream {
  function close();
  function canWrite();

  function writeResult($_name_, Framework\TestResult $_result_);
  function writeSummary($_passed_, $_suites_count_, $_tests_count_);
}

// }}} #############################################################################################
// {{{ TestHarness

class TestHarness {
  private
    $_outStream,
    $_runner;

  function __construct(
    TestHarnessOutStream $_outStream_,
    Framework\TestErrStream $_errStream_ = \NULL
  ) {
    $this->_outStream = $_outStream_;

    $producer = new Framework\TestProducer(
      new _\NoopTestOutStream(),
      $_errStream_ ?: new _\NoopTestErrStream());

    $this->_runner = new TestRunner($producer);
  }

  function executeTestFiles(array $_files_) {
    $tests_passed = \TRUE;
    $tests_count  = 0;

    $count = \count($_files_);

    for ($i = 0; $i < $count; $i++) {
      $suite = new Suites\FileTestSuite($_files_[$i]);

      $result = $this->_runner->run($suite);

      $this->_outStream->writeResult($suite->getName(), $result);

      if (!$result->passed) {
        $tests_passed = \FALSE;
      }

      $tests_count += $result->testsCount;
    }

    $this->_outStream->writeSummary($tests_passed, \count($_files_), $tests_count);
  }
}

// }}} #############################################################################################

namespace Narvalo\Test\Runner\Internal;

use \Narvalo\Test\Framework;

// {{{ RuntimeErrorCatcher

final class RuntimeErrorCatcher {
  private
    // PHP display_errors on/off.
    //$_phpDisplayErrors,
    // PHP error reporting level.
    $_phpErrorReporting,
    $_producer;

  function __construct(Framework\TestProducer $_producer_) {
    $this->_producer = $_producer_;
  }

  function start() {
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

    \set_error_handler(
      function ($errno , $errstr, $errfile, $errline, $errcontext) use (&$errors) {
        $this->_producer->captureRuntimeError("Error at {$errfile} line {$errline}.\n$errstr");
      }
    );
  }

  function stop() {
    \restore_error_handler();
    // Restore PHP settings.
    //ini_set('display_errors', $this->_phpDisplayErrors);
    \error_reporting($this->_phpErrorReporting);
  }
}

// }}} #############################################################################################
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

// }}} #############################################################################################
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

// }}}

// EOF
