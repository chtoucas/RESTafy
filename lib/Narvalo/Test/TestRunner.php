<?php

namespace Narvalo\Test;

require_once 'NarvaloBundle.php';
require_once 'Narvalo\Test\Framework.php';

use \Narvalo;
use \Narvalo\Test\Framework;

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
    // Restore PHP settings
    //ini_set('display_errors', $this->_phpDisplayErrors);
    \error_reporting($this->_phpErrorReporting);
  }

  function pushError($_error_) {
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
  use Narvalo\Singleton;

  const
    SUCCESS_CODE = 0,
    FATAL_CODE   = 255;

  private $_helper;

  private function _initialize() {
    $this->_helper = new TestRunnerHelper();
  }

  function runTest($_test_) {
    // Override default error handler.
    $this->_helper->overrideErrorHandler();
    // Run the test specification.
    try {
      $loaded = include_once $_test_;
    } catch (Framework\NormalTestProducerInterrupt $e) {
      ;
    } catch (Framework\FatalTestProducerInterrupt $e) {
      ;
    } catch (\Exception $e) {
      $this->_helper->pushError('Unexpected error: ' . $e->getMessage());
      goto TERMINATE;
    }

    if (\FALSE === $loaded) {
    }

    TERMINATE: {
      $mod = new Framework\TestModule();
      $producer = $mod->getProducer();
    }

    // Restore default error handler.
    $this->_helper->restoreErrorHandler();
    $errors_count = $this->_helper->writeErrors($producer->getErrStream());
    //
    if ($errors_count > 0) {
      $exit_code = self::FATAL_CODE;
    } else {
      $exit_code = $this->getExitCode($producer);
    }
    //
    $this->terminate($exit_code);
  }

  protected function terminate($_code_) {
    exit($_code_);
  }

  protected function getExitCode($_producer_) {
    if ($_producer_->passed()) {
      // All tests passed.
      $code = self::SUCCESS_CODE;
    } else if (($count = $_producer_->getFailuresCount()) > 0) {
      // There are failed tests.
      $code = $count < self::FATAL_CODE ? $count : (self::FATAL_CODE - 1);
    } else {
      // Other kind of errors: extra tests, unattended interrupt.
      $code = self::FATAL_CODE;
    }
    return $code;
  }
}

// EOF
