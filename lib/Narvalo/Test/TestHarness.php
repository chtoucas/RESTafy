<?php

namespace Narvalo\Test;

require_once 'Narvalo\Test\FrameworkBundle.php';

use \Narvalo\Test\Framework;

final class TestHarness {
  const
    SUCCESS_CODE = 0,
    FATAL_CODE   = 255;

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

  function runTests($_tests_) {
    // Open streams.
    // Create a new test producer: no output at all.
    $producer = new Framework\TestProducer(
      new Framework\NullOutStream(), new Framework\NullErrStream());
    Framework\TestModule::Initialize($producer);
    // Override default error handler.
    $this->_overrideErrorHandler();
    // Run the test suite.
    foreach ($_tests_ as $test) {
      try {
        // We turn off error reporting otherwise we will have duplicate errors.
        // We eval the code otherwise the include call may abort the whole script.
        //$errlevel = ini_get('error_reporting');
        //error_reporting(0);
        $exit_code = include_once $test;
        //error_reporting($errlevel);
      } catch (Framework\TestProducerException $e) {
        // XXX
        $exit_code = $e->getCode();
      } catch (\Exception $e) {
        exit('Unexpected error: ' . $e->getMessage());
      }

      if (self::SUCCESS_CODE === $exit_code) {
        $status= 'OK';
      } else if ($exit_code > self::SUCCESS_CODE
        && $exit_code <= self::FATAL_CODE
      ) {
        $status = 'KO';
      } else if (\FALSE === $exit_code) {
        $status = 'NOT FOUND';
      } else {
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
      } else {
        $statusline = $test . '...'. $status;
      }

      echo $statusline, "\n";

      // Reset.
      //$producer->reset();
    }
    // Restore default error handler.
    $this->_restoreErrorHandler();
  }

  private function _overrideErrorHandler() {
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

// EOF
