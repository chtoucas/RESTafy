<?php

namespace Narvalo\Test\Runner;

require_once 'NarvaloBundle.php';
require_once 'Narvalo\Test\FrameworkBundle.php';

use \Narvalo;
use \Narvalo\Test\Framework;
use \Narvalo\Test\Runner\Internal as _;

// {{{ TestRunner

final class TestResult {
  public
    $passed,
    $loaded,
    $bailedOut = \FALSE,
    $hiddenErrorsCount = 0,
    $failuresCount = 0,
    $testsCount = 0;
}

class TestRunner {
  private
    $_errorCatcher,
    $_producer;

  function __construct(Framework\TestProducer $_producer_) {
    $this->_producer     = $_producer_;
    $this->_errorCatcher = new _\RuntimeErrorCatcher();
    // FIXME
    self::_InitializeKernel($_producer_, \TRUE);
  }

  function runTest($_test_file_) {
    Narvalo\Guard::NotEmpty($_test_file_, 'test_file');

    $this->_producer->startup();

    // Override the default error handler.
    $this->_errorCatcher->overrideErrorHandler();

    try {
      $loaded = Narvalo\DynaLoader::LoadFile($_test_file_);
    } catch (Narvalo\FileNotFoundRuntimeException $e) {
      $loaded = \FALSE;
    } catch (Framework\TestProducerInterrupt $e) {
      $loaded = \TRUE;
    } catch (\Exception $e) {
      // FIXME: Are exceptions thrown by tests correctly handled?
      $loaded = \TRUE;
      $this->_errorCatcher->pushException($e);
    }

    // Restore the default error handler.
    $this->_errorCatcher->restoreErrorHandler();

    $this->_producer->shutdown($loaded);

    // Write hidden errors to the error stream.
    $hidden_errors_count
      = $this->_errorCatcher->writeErrorsTo($this->_producer->getErrStream(), \TRUE);

    $result = new TestResult();
    $result->loaded            = $loaded;
    $result->passed            = $loaded && $this->_producer->passed();
    $result->bailedOut         = $this->_producer->bailedOut();
    $result->hiddenErrorsCount = $hidden_errors_count;
    $result->failuresCount     = $this->_producer->getFailuresCount();
    $result->testsCount        = $this->_producer->getTestsCount();

    // Reset the producer.
    $this->_producer->reset();

    return $result;
  }

  private static function _InitializeKernel($_producer_, $_throwIfCalledtwice_) {
    Framework\TestModulesKernel::Bootstrap($_producer_, $_throwIfCalledtwice_);
  }
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

    $this->_runner = new TestRunner($producer);
  }

  function runTests(array $_test_files_) {
    $tests_passed = \TRUE;
    $tests_count  = 0;

    // Run the test suite.
    foreach ($_test_files_ as $test_file) {
      $result = $this->_runner->runTest($test_file);

      // FIXME: The remaining code should not be here.

      if ($result->passed) {
        $status = 'ok';
      } else {
        $tests_passed = \FALSE;

        if (!$result->loaded) {
          $status = 'NOT FOUND!';
        } else if ($result->bailedOut) {
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
    // Errors list.
    $_errors = array();

  function __construct() {
    ;
  }

  function getErrorsCount() {
    return \count($this->_errors);
  }

  function overrideErrorHandler() {
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
    // Restore PHP settings.
    //ini_set('display_errors', $this->_phpDisplayErrors);
    \error_reporting($this->_phpErrorReporting);
  }

  function reset() {
    $this->_errors = array();
  }

  function pushError($_error_) {
    \array_push($this->_errors, $_error_);
  }

  function pushException(\Exception $_ex_) {
    \array_push($this->_errors, 'Unexpected error: ' . $_ex_->getMessage());
  }

  function writeErrorsTo(Framework\TestErrStream $_errStream_, $_resetAfter_) {
    $count = $this->getErrorsCount();
    if ($count > 0) {
      for ($i = 0; $i < $count; $i++) {
        $_errStream_->write($this->_errors[$i]);
      }
      //\trigger_error('There are hidden errors.', \E_USER_WARNING);
    }
    if ($_resetAfter_) {
      $this->reset();
    }
    return $count;
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
