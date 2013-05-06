<?php

namespace Narvalo\Test\Runner;

require_once 'Narvalo\Test\FrameworkBundle.php';

use \Narvalo\Test\Framework;
use \Narvalo\Test\Runner\Internal as _;

// {{{ TestRunner

class TestRunner {
  private
    $_errorCatcher,
    $_producer;

  function __construct(Framework\TestProducer $_producer_) {
    $this->_producer     = $_producer_;
    $this->_errorCatcher = new _\RuntimeErrorCatcher();
  }

  protected function getProducer_() {
    return $this->_producer;
  }

  function runTest($_test_) {
    Framework\TestModule::Initialize($this->_producer);

    // Override default error handler.
    $this->_errorCatcher->overrideErrorHandler();

    // Run the test.
    $loaded = \FALSE;

    try {
      $this->_producer->startup();

      $loaded = \FALSE !== (include_once $_test_);

      $this->_producer->shutdown($loaded);
    } catch (Framework\SkipTestProducerInterrupt $e) {
      $loaded = \TRUE;
    } catch (Framework\BailOutTestProducerInterrupt $e) {
      $loaded = \TRUE;
    } catch (\Exception $e) {
      $this->_errorCatcher->pushException($e);
    }

    // Restore default error handler.
    $this->_errorCatcher->restoreErrorHandler();
    $hidden_errors_count = $this->_errorCatcher->writeErrorsTo($this->_producer->getErrStream());
    $this->_errorCatcher->reset();

    return $hidden_errors_count;
  }
}

// }}} #############################################################################################
// {{{ TestHarness

final class TestHarness {
  private
    $_errorCatcher,
    $_producer;

  function __construct() {
    // No output at all.
    $this->_producer = new Framework\TestProducer(
      new _\NoopTestOutStream(), new _\NoopTestErrStream());

    $this->_errorCatcher = new _\RuntimeErrorCatcher();
  }

  function runTests(array $_tests_) {
    Framework\TestModule::Initialize($this->_producer);

    $tests_passed = \TRUE;
    $tests_count  = 0;

    // Override default error handler.
    $this->_errorCatcher->overrideErrorHandler();

    // Run the test suite.
    foreach ($_tests_ as $test) {
      $loaded = \FALSE;

      try {
        $this->_producer->startup();

        $loaded = \FALSE !== (include_once $test);

        $this->_producer->shutdown($loaded);
      } catch (Framework\SkipTestProducerInterrupt $e) {
        $loaded = \TRUE;
      } catch (Framework\BailOutTestProducerInterrupt $e) {
        $loaded = \TRUE;
      } catch (\Exception $e) {
        $this->_errorCatcher->pushException($e);
      }

      $passed = $loaded && $this->_producer->passed();

      if ($passed) {
        $status = 'ok';
      } else {
        $tests_passed = \FALSE;

        if (!$loaded) {
          $status = 'NOT FOUND';
        } else if ($this->_producer->bailedOut()) {
          $status = 'BAIL OUT!';
        } else {
          $status = 'ko';
        }
      }

      // Restore default error handler.
      $this->_errorCatcher->restoreErrorHandler();
      $hidden_errors_count = $this->_errorCatcher->getErrorsCount();

      if ($hidden_errors_count > 0) {
        // There are hidden errors. See diagnostics above
        $status .= ' DUBIOUS';
      }

      if (($dotlen = 40 - \strlen($test)) > 0) {
        $statusline = $test . \str_repeat('.', $dotlen) . ' ' . $status;
      } else {
        $statusline = $test . '... '. $status;
      }

      echo $statusline, \PHP_EOL;

      if ($loaded && !$this->_producer->bailedOut() && !$this->_producer->passed()) {
        echo \sprintf(
          'Failed %s/%s subtests%s',
          $this->_producer->getFailuresCount(),
          $this->_producer->getTestsCount(),
          \PHP_EOL);
      }

      $tests_count += $this->_producer->getTestsCount();

      // Reset all.
      $this->_errorCatcher->reset();
      $this->_producer->reset();
    }

    if ($tests_passed) {
      echo 'All tests successful.', \PHP_EOL;
    }
    echo \sprintf('Files=%s, Tests=%s%s', \count($_tests_), $tests_count, \PHP_EOL);
    echo \sprintf('Result: %s%s', ($tests_passed ? 'PASS' : 'FAIL'), \PHP_EOL);
  }
}

// }}} #############################################################################################

// {{{ Internal

namespace Narvalo\Test\Runner\Internal;

use \Narvalo\Test\Framework;

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

  function writeErrorsTo(Framework\TestErrStream $_errStream_) {
    $count = $this->getErrorsCount();
    if ($count > 0) {
      for ($i = 0; $i < $count; $i++) {
        $_errStream_->write($this->_errors[$i]);
      }
      \trigger_error('There are hidden errors.', \E_USER_WARNING);
    }
    return $count;
  }
}

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

  function writeTestCase(Framework\DefaultTestCase $_test_, $_number_) {
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
