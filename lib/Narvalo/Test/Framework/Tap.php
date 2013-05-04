<?php

namespace Narvalo\Test\Framework\Tap;

require_once 'Narvalo\Test\Framework.php';

use Narvalo\Test\Framework;

define('CRLF_REGEX_PART',       '(?:\r|\n)+');
/// RegEx to find any combination of \r and \n in a string.
define('CRLF_REGEX',            '{' . CRLF_REGEX_PART . '}');
/// RegEx to find any combination of \r and \n at the end of a string.
define('TRAILING_CRLF_REGEX',   '{' . CRLF_REGEX_PART . '\z}s');
/// RegEx to find any combination of \r and \n inside a normalized string.
define('MULTILINE_CRLF_REGEX',  '{' . CRLF_REGEX_PART . '(?!\z)}');

class TapOutStream extends Framework\FileStream implements Framework\OutStream {
  // FIXME: find the correct version in use.
  const Version = '13';

  protected $verbose;

  function __construct($_path_, $_verbose_) {
    parent::__construct($_path_);
    $this->verbose = $_verbose_;
  }

  protected function cleanup($_disposing_) {
    if (!$this->Opened()) {
      return;
    }
    // FIXME Close workflow
    parent::cleanup($_disposing_);
  }

  static function FormatDescription($_desc_) {
    // Escape EOL.
    $desc = preg_replace(CRLF_REGEX, '¤', $_desc_);
    // Escape leading unsafe chars.
    $desc = preg_replace('{^[\d\s]+}', '¤', $desc);
    // Escape #.
    $desc = str_replace('#', '\\#', $desc);
    if ($desc != $_desc_) {
      trigger_error("The description '$_desc_' contains invalid chars.", E_USER_NOTICE);
    }
    return $desc;
  }

  static function FormatReason($_reason_) {
    $reason = preg_replace(CRLF_REGEX, '¤', $_reason_);
    if ($reason != $_reason_) {
      trigger_error("The reason '$_reason_' contains invalid chars.", E_USER_NOTICE);
    }
    return $reason;
  }

  /// \return integer
  function startSubTest() {
    $this->indent();
  }

  /// \return void
  function endSubTest() {
    $this->unindent();
  }

  function writeHeader() {
    return $this->writeLine('TAP version ' . self::Version);
  }

  function writeFooter() {
    ;
  }

  function writePlan($_num_of_tests_) {
    return $this->writeLine('1..' . $_num_of_tests_);
  }

  function writeSkipAll($_reason_) {
    return $this->writeLine('1..0 skip ' . self::FormatReason($_reason_));
  }

  function writeTestCase(Framework\TestCase $_test_, $_number_) {
    $desc = self::FormatDescription($_test_->getDescription());
    $line = sprintf('%s %d - %s', $_test_->Passed() ? 'ok' : 'not ok', $_number_, $desc);
    return $this->writeLine($line);
  }

  function writeTodoTestCase(Framework\TodoTestCase $_test_, $_number_) {
    $reason = self::FormatReason($_test_->reason());
    $line = sprintf('ok %d # SKIP %s', $_number_, $reason);
    return $this->writeLine($line);
  }

  function writeSkipTestCase(Framework\SkipTestCase $_test_, $_number_) {
    $desc   = self::FormatDescription($_test_->getDescription());
    $reason = self::FormatReason($_test_->reason());
    $line = sprintf('%s %d - %s # TODO %s',
      $_test_->Passed() ? 'ok' : 'not ok', $_number_, $desc, $reason);
    return $this->writeLine($line);
  }

  function writeBailOut($_reason_) {
    return $this->rawWrite('Bail out! ' . self::FormatReason($_reason_) . self::EndOfLine());
  }

  function writeComment($_comment_) {
    if (!$this->verbose) {
      return;
    }
    return $this->write( $this->FormatMultiLine('# ', $_comment_) );
  }
}

class StandardTapOutStream extends TapOutStream {
  function __construct($_verbose_) {
    // FIXME STDOUT
    parent::__construct('php://stdout', $_verbose_);
  }
}

class InMemoryTapOutStream extends TapOutStream {
  function __construct($_verbose_) {
    parent::__construct('php://memory', $_verbose_);
  }
}

class TapErrStream extends Framework\FileStream implements Framework\ErrStream {
  protected function cleanup($_disposing_) {
    if (!$this->Opened()) {
      return;
    }
    parent::cleanup($_disposing_);
  }

  /// \return integer
  function startSubTest() {
    $this->indent();
  }

  /// \return void
  function endSubTest() {
    $this->unindent();
  }

  function write($_value_) {
    return parent::write( $this->FormatMultiLine('# ', $_value_) );
  }
}

class StandardTapErrStream extends TapErrStream {
  function __construct() {
    // FIXME STDERR
    parent::__construct('php://stderr');
  }
}

/// NB: Only one TapRunner may exist at a given time.
final class TapRunner {
  const
    CODE_SUCCESS = 0,
    CODE_FATAL   = 255;

  protected
    $producer;

  private static
    $_Instance;

  private
    // Errors list.
    $_errors     = array(),
      // PHP display_errors on/off.
      //$_phpDisplayErrors,
      // PHP error reporting level.
      $_phpErrorReporting;

  private function __construct(Framework\TestProducer $_producer_) {
    $this->producer = $_producer_;
  }

  final private function __clone() {
    ;
  }

  /// Singleton method.
  static function UniqInstance(Framework\TestProducer $_producer_ = \NULL) {
    if (\NULL === self::$_Instance) {
      self::$_Instance = new self(
        $_producer_ != \NULL ? $_producer_ : self::_defaultProducer());
    }
    return self::$_Instance;
  }

  function runTest($_test_) {
    //
    Framework\TestModule::Initialize($this->producer);
    // Override default error handler.
    $this->_overrideErrorHandler();
    // Run the test specification.
    try {
      $loaded = include_once $_test_;
    }
    catch (Framework\NormalTestProducerInterrupt $e) {
      ;
    }
    catch (Framework\FatalTestProducerInterrupt $e) {
      ;
    }
    catch (\Exception $e) {
      array_push($this->_errors, 'Unexpected error: ' . $e->getMessage());
      $this->_restoreErrorHandler();
      $this->_displayErrors($this->producer->ErrStream());
      $this->terminate(self::CODE_FATAL);
    }

    if (\FALSE === $loaded) {
    }

    // Restore default error handler.
    $this->_restoreErrorHandler();
    // Report on unhandled errors and exceptions.
    if (($count = count($this->_errors)) > 0) {
      $this->_displayErrors($this->producer->ErrStream());
      $this->terminate(self::CODE_FATAL);
    }
    // Exit!
    $this->terminate( $this->exitCode($this->producer) );
  }

  protected function terminate($_code_) {
    exit($_code_);
  }

  protected function exitCode($_producer_) {
    if ($_producer_->Passed()) {
      // All tests passed and no abnormal error.
      $code = self::CODE_SUCCESS;
    }
    else if (($count = $_producer_->FailuresCount()) > 0) {
      // There are failed tests.
      $code = $count < self::CODE_FATAL ? $count : (self::CODE_FATAL - 1);
    }
    else {
      // Other kind of errors: extra tests, unattended interrupt.
      $code = self::CODE_FATAL;
    }
    return $code;
  }

  private function _defaultProducer() {
    return new Framework\TestProducer(new StandardTapOutStream(\TRUE), new StandardTapErrStream());
  }

  private function _overrideErrorHandler() {
    // One way or another, we want to see all errors.
    //$this->_phpDisplayErrors  = ini_get('display_errors');
    $this->_phpErrorReporting = ini_get('error_reporting');
    //ini_set('display_errors', 'Off');
    error_reporting(E_ALL | E_STRICT);
    // Beware we can not catch all errors.
    // See: http://php.net/manual/en/function.set-error-handler.php
    // The following error types cannot be handled with a user defined
    // function: E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING,
    // E_COMPILE_ERROR, E_COMPILE_WARNING, and most of E_STRICT raised in
    // the file where set_error_handler() is called.
    $errors =& $this->_errors;
    set_error_handler(
      function ($errno , $errstr, $errfile, $errline, $errcontext) use (&$errors) {
        array_push($errors, "Error at {$errfile} line {$errline}.\n$errstr");
      }
    );
  }

  private function _restoreErrorHandler() {
    restore_error_handler();
    // Restore PHP settings
    //ini_set('display_errors', $this->_phpDisplayErrors);
    error_reporting($this->_phpErrorReporting);
  }

  private function _displayErrors(Framework\ErrStream $_errStream_) {
    if (($count = count($this->_errors)) > 0) {
      for ($i = 0; $i < $count; $i++) {
        $_errstream_->write($this->_errors[$i]);
      }
      trigger_error('There are hidden errors', E_USER_WARNING);
    }
  }
}

// EOF
