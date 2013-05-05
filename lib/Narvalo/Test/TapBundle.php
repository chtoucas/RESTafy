<?php
// Cf.
// - http://testanything.org/wiki/index.php/Main_Page
// - http://search.cpan.org/~petdance/Test-Harness-2.65_02/lib/Test/Harness/TAP.pod
// TAP specification
// - http://podwiki.hexten.net/TAP/TAP13.html?page=TAP13
// - http://podwiki.hexten.net/TAP/TAP.html?page=TAP

namespace Narvalo\Test\Tap;

require_once 'NarvaloBundle.php';
require_once 'Narvalo\Test\FrameworkBundle.php';
require_once 'Narvalo\Test\TestRunner.php';

use Narvalo;
use Narvalo\Test;
use Narvalo\Test\Framework;

define('CRLF_REGEX_PART',       '(?:\r|\n)+');
/// RegEx to find any combination of \r and \n in a string.
define('CRLF_REGEX',            '{' . CRLF_REGEX_PART . '}');
/// RegEx to find any combination of \r and \n at the end of a string.
define('TRAILING_CRLF_REGEX',   '{' . CRLF_REGEX_PART . '\z}s');
/// RegEx to find any combination of \r and \n inside a normalized string.
define('MULTILINE_CRLF_REGEX',  '{' . CRLF_REGEX_PART . '(?!\z)}');

// {{{ OutStream

class TapOutStream extends Framework\FileStream implements Framework\OutStream {
  // FIXME: check the correct version in use.
  const Version = '13';

  protected $verbose;

  function __construct($_path_, $_verbose_) {
    parent::__construct($_path_);
    $this->verbose = $_verbose_;
  }

  protected function cleanup($_disposing_) {
    if (!$this->opened()) {
      return;
    }
    // FIXME Close workflow
    parent::cleanup($_disposing_);
  }

  static function FormatDescription($_desc_) {
    // Escape EOL.
    $desc = \preg_replace(CRLF_REGEX, '¤', $_desc_);
    // Escape leading unsafe chars.
    $desc = \preg_replace('{^[\d\s]+}', '¤', $desc);
    // Escape #.
    $desc = \str_replace('#', '\\#', $desc);
    if ($desc != $_desc_) {
      trigger_error("The description '$_desc_' contains invalid chars.", E_USER_NOTICE);
    }
    return $desc;
  }

  static function FormatReason($_reason_) {
    $reason = \preg_replace(CRLF_REGEX, '¤', $_reason_);
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

  function writeTestCase(Framework\DefaultTestCase $_test_, $_number_) {
    $desc = self::FormatDescription($_test_->getDescription());
    $line = \sprintf('%s %d - %s', $_test_->passed() ? 'ok' : 'not ok', $_number_, $desc);
    return $this->writeLine($line);
  }

  function writeTodoTestCase(Framework\TodoTestCase $_test_, $_number_) {
    $reason = self::FormatReason($_test_->getReason());
    $line = \sprintf('ok %d # SKIP %s', $_number_, $reason);
    return $this->writeLine($line);
  }

  function writeSkipTestCase(Framework\SkipTestCase $_test_, $_number_) {
    $desc   = self::FormatDescription($_test_->getDescription());
    $reason = self::FormatReason($_test_->getReason());
    $line = \sprintf('%s %d - %s # TODO %s',
      $_test_->passed() ? 'ok' : 'not ok', $_number_, $desc, $reason);
    return $this->writeLine($line);
  }

  function writeBailOut($_reason_) {
    return $this->rawWrite('Bail out! ' . self::FormatReason($_reason_) . self::EOL);
  }

  function writeComment($_comment_) {
    if (!$this->verbose) {
      return;
    }
    return $this->write( $this->FormatMultiLine('# ', $_comment_) );
  }
}

class DefaultTapOutStream extends TapOutStream {
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

// }}} #############################################################################################
// {{{ ErrStream

class TapErrStream extends Framework\FileStream implements Framework\ErrStream {
  protected function cleanup($_disposing_) {
    if (!$this->opened()) {
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

class DefaultTapErrStream extends TapErrStream {
  function __construct() {
    // FIXME STDERR
    parent::__construct('php://stderr');
  }
}

// }}} #############################################################################################
// {{{ DefaultTapProducer

final class DefaultTapProducer extends Framework\TestProducer {
  public function __construct() {
    parent::__construct(new DefaultTapOutStream(\TRUE), new DefaultTapErrStream());
  }
}

// }}} #############################################################################################
// {{{ TapRunner

final class TapRunner {
  use Narvalo\Singleton;

  const
    SUCCESS_CODE = 0,
    FATAL_CODE   = 255;

  private
      $_helper,
      $_producer;

  private function _initialize() {
    $this->_producer = new DefaultTapProducer();
    $this->_helper = new Test\TestRunnerHelper();
  }

  function runTest($_test_) {
    // FIXME
    Framework\TestModule::Initialize($this->_producer);

    // Override default error handler.
    $this->_helper->overrideErrorHandler();

    // Run the test.
    $loaded = \FALSE;

    try {
      $loaded = include_once $_test_;
    } catch (Framework\NormalTestProducerInterrupt $e) {
      ;
    } catch (Framework\FatalTestProducerInterrupt $e) {
      ;
    } catch (\Exception $e) {
      $this->_helper->pushError('Unexpected error: ' . $e->getMessage());

      goto TERMINATE;

      //$this->_helper->restoreErrorHandler();
      //$this->_helper->writeErrors($this->_producer->getErrStream());

      //$this->terminate(self::FATAL_CODE);
    }

    if (\FALSE === $loaded) {
      // FIXME
    }

    TERMINATE: {
      // Restore default error handler.
      $this->_helper->restoreErrorHandler();
      $errors_count = $this->_helper->writeErrors($this->_producer->getErrStream());

      // Report on unhandled errors and exceptions.
      if ($errors_count > 0) {
        $exit_code = self::FATAL_CODE;
      } else {
        $exit_code = $this->getExitCode($this->_producer);
      }

      // Exit!
      $this->terminate($exit_code);
    }

    // Report on unhandled errors and exceptions.
    //if (($count = \count($this->_errors)) > 0) {
    //  $this->_displayErrors($this->_producer->getErrStream());
    //  $this->terminate(self::FATAL_CODE);
    //}
    // Exit!
    //$this->terminate( $this->getExitCode($this->_producer) );
  }

  protected function terminate($_code_) {
    exit($_code_);
  }

  protected function getExitCode($_producer_) {
    if ($_producer_->passed()) {
      // All tests passed and no abnormal error.
      $code = self::SUCCESS_CODE;
    }
    else if (($count = $_producer_->getFailuresCount()) > 0) {
      // There are failed tests.
      $code = $count < self::FATAL_CODE ? $count : (self::FATAL_CODE - 1);
    }
    else {
      // Other kind of errors: extra tests, unattended interrupt.
      $code = self::FATAL_CODE;
    }
    return $code;
  }
}

// }}}

// EOF
