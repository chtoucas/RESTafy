<?php

namespace Narvalo\Test\Runner;

require_once 'NarvaloBundle.php';
require_once 'Narvalo\Test\FrameworkBundle.php';

use \Narvalo;
use \Narvalo\Test\Framework;
use \Narvalo\Test\Runner\Internal as _;

// {{{ FileTestSuite

class FileTestSuite extends Framework\AbstractTestSuite {
  private $_file;

  function __construct($_file_) {
    $this->_file = $_file_;
  }

  function execute() {
    Narvalo\DynaLoader::LoadFile($this->_file);
  }
}

// }}} #############################################################################################
// {{{ TestRunner

final class TestRunner {
  private
    $_errorCatcher,
    $_producer;
  //$_testCompletedHandlers;

  function __construct(Framework\TestProducer $_producer_) {
    $this->_producer     = $_producer_;
    $this->_errorCatcher = new _\RuntimeErrorCatcher($_producer_);
    //$this->_testCompletedHandlers = new \SplObjectStorage();
  }

  function runTestSuite(Framework\TestSuite $_test_suite_) {
    Narvalo\Guard::NotNull($_test_suite_, 'test_suite');

    $this->_producer->startup();
    $this->_errorCatcher->start();

    try {
      $_test_suite_->setup();
      $_test_suite_->execute();
    } catch (Framework\TestProducerInterrupt $e) {
      ;
    } catch (\Exception $e) {
      $this->_producer->bailOutOnException($e);
    }

    $_test_suite_->teardown();

    $this->_errorCatcher->stop();
    return $this->_producer->shutdown();

    //$this->onTestCompleted($result);
  }

  function runTestFile($_test_file_) {
    Narvalo\Guard::NotEmpty($_test_file_, 'test_file');

    $test_suite = new FileTestSuite($_test_file_);

    return $this->runTestSuite($test_suite);
  }

  //  function addTestCompletedHandler(\Closure $_handler_) {
  //    $this->_testCompletedHandlers->attach($_handler_);
  //  }
  //
  //  function onTestCompleted(TestResult $_result_) {
  //    foreach ($this->_testCompletedHandlers as $handler) {
  //      $handler($this, $_result_);
  //    }
  //  }
}

// }}} #############################################################################################
// {{{ TestHarness

// TODO:
// - scan and run (continuously or after scan completed)
class TestHarness {
  private $_runner;

  function __construct(Framework\TestErrStream $_errStream_ = \NULL) {
    $errStream = $_errStream_ ?: new _\NoopTestErrStream();
    $producer = new Framework\TestProducer(new _\NoopTestOutStream(), $errStream);
    Framework\TestModulesKernel::Bootstrap($producer, \TRUE);

    $this->_runner = new TestRunner($producer);
  }

  function processFiles(array $_test_files_) {
    $tests_passed = \TRUE;
    $tests_count  = 0;

    // Run the test suite.
    foreach ($_test_files_ as $test_file) {
      $result = $this->_runner->runTestFile($test_file);

      // FIXME: The remaining code should not be here.

      if ($result->passed) {
        $status = 'ok';
      } else {
        $tests_passed = \FALSE;

        if ($result->bailedOut) {
          $status = 'BAILED OUT!';
        } else {
          $status = 'KO';
        }
      }

      if ($result->hiddenErrorsCount > 0) {
        // There are hidden errors. See diagnostics above
        $status .= ' DUBIOUS';
      }

      if (($dotlen = 40 - \strlen($test_file)) > 0) {
        $statusline = $test_file . \str_repeat('.', $dotlen) . ' ' . $status;
      } else {
        $statusline = $test_file . '... '. $status;
      }

      echo $statusline, \PHP_EOL;

      if (!$result->passed) {
        echo \sprintf(
          'Failed %s/%s subtests%s',
          $result->failuresCount,
          $result->testsCount,
          \PHP_EOL);
      }

      $tests_count += $result->testsCount;
    }

    if ($tests_passed) {
      echo 'All tests successful.', \PHP_EOL;
    }
    echo \sprintf('Files=%s, Tests=%s%s', \count($_test_files_), $tests_count, \PHP_EOL);
    echo \sprintf('Result: %s%s', ($tests_passed ? 'PASS' : 'FAIL'), \PHP_EOL);
  }
}

// }}} #############################################################################################

namespace Narvalo\Test\Runner\Internal;

use \Narvalo\Test\Framework;

// {{{ RuntimeErrorCatcher

class RuntimeErrorCatcher {
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
        $this->_producer->recordHiddenError("Error at {$errfile} line {$errline}.\n$errstr");
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
// {{{ NoopStreams

class NoopTestOutStream implements Framework\TestOutStream {
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

class NoopTestErrStream implements Framework\TestErrStream {
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
