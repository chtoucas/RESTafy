<?php
// Cf.
// - http://testanything.org/wiki/index.php/Main_Page
// - http://search.cpan.org/~petdance/Test-Harness-2.65_02/lib/Test/Harness/TAP.pod
// TAP specification
// - http://podwiki.hexten.net/TAP/TAP13.html?page=TAP13
// - http://podwiki.hexten.net/TAP/TAP.html?page=TAP

namespace Narvalo\Test\Tap;

require_once 'Narvalo\Test\FrameworkBundle.php';
require_once 'Narvalo\Test\RunnerBundle.php';

use \Narvalo\Test\Framework;
use \Narvalo\Test\Runner;

define('CRLF_REGEX_PART',       '(?:\r|\n)+');
// RegEx to find any combination of \r and \n in a string.
define('CRLF_REGEX',            '{' . CRLF_REGEX_PART . '}');
// RegEx to find any combination of \r and \n at the end of a string.
define('TRAILING_CRLF_REGEX',   '{' . CRLF_REGEX_PART . '\z}s');
// RegEx to find any combination of \r and \n inside a normalized string.
define('MULTILINE_CRLF_REGEX',  '{' . CRLF_REGEX_PART . '(?!\z)}');

// FIXME: this class should be internal.
// {{{ TapStream

class TapStream extends Framework\StreamWriter {
  const EOL = "\n";

  private $_indent = '';

  function reset() {
    $this->_indent = '';
  }

  function write($_value_) {
    return parent::write($this->_indent . $_value_ . self::EOL);
  }

  protected function indent_() {
    $this->_indent = '    ' . $this->_indent;
  }

  protected function unindent_() {
    $this->_indent = \substr($this->_indent, 4);
  }

  protected function formatMultiLine_($_prefix_, $_value_) {
    $prefix = $this->_indent . $_prefix_;
    $value = \preg_replace(TRAILING_CRLF_REGEX, '', $_value_);

    return $_prefix_ . \preg_replace(MULTILINE_CRLF_REGEX, $prefix, $value);
  }
}

// }}} #############################################################################################

// {{{ TapOutStream

class TapOutStream extends TapStream implements Framework\TestOutStream {
  const VERSION = '13';

  private $_verbose;

  function __construct($_path_, $_verbose_) {
    parent::__construct($_path_);

    $this->_verbose = $_verbose_;
  }

  /// \return integer
  function startSubTest() {
    $this->indent_();
  }

  /// \return void
  function endSubTest() {
    $this->unindent_();
  }

  function writeHeader() {
    return $this->write('TAP version ' . self::VERSION);
  }

  function writeFooter() {
    ;
  }

  function writePlan($_num_of_tests_) {
    return $this->write('1..' . $_num_of_tests_);
  }

  function writeSkipAll($_reason_) {
    return $this->write('1..0 skip ' . self::_FormatReason($_reason_));
  }

  function writeTestCase(Framework\TestCase $_test_, $_number_) {
    $desc = self::_FormatDescription($_test_->getDescription());
    $line = \sprintf('%s %d - %s', $_test_->passed() ? 'ok' : 'not ok', $_number_, $desc);
    return $this->write($line);
  }

  function writeTodoTestCase(Framework\TodoTestCase $_test_, $_number_) {
    $reason = self::_FormatReason($_test_->getReason());
    $line = \sprintf('ok %d # SKIP %s', $_number_, $reason);
    return $this->write($line);
  }

  function writeSkipTestCase(Framework\SkipTestCase $_test_, $_number_) {
    $desc   = self::_FormatDescription($_test_->getDescription());
    $reason = self::_FormatReason($_test_->getReason());
    $line = \sprintf('%s %d - %s # TODO %s',
      $_test_->passed() ? 'ok' : 'not ok', $_number_, $desc, $reason);
    return $this->write($line);
  }

  function writeBailOut($_reason_) {
    return $this->write('Bail out! ' . self::_FormatReason($_reason_));
  }

  function writeComment($_comment_) {
    if (!$this->_verbose) {
      return;
    }
    return $this->write($this->formatMultiLine_('# ', $_comment_));
  }

  private static function _FormatDescription($_desc_) {
    // Escape EOL.
    $desc = \preg_replace(CRLF_REGEX, '¤', $_desc_);
    // Escape leading unsafe chars.
    $desc = \preg_replace('{^[\d\s]+}', '¤', $desc);
    // Escape #.
    $desc = \str_replace('#', '\\#', $desc);
    if ($desc != $_desc_) {
      \trigger_error("The description '$_desc_' contains invalid chars.", \E_USER_NOTICE);
    }
    return $desc;
  }

  private static function _FormatReason($_reason_) {
    $reason = \preg_replace(CRLF_REGEX, '¤', $_reason_);
    if ($reason != $_reason_) {
      \trigger_error("The reason '$_reason_' contains invalid chars.", \E_USER_NOTICE);
    }
    return $reason;
  }
}

// }}} #############################################################################################
// {{{ TapErrStream

class TapErrStream extends TapStream implements Framework\TestErrStream {
  /// \return integer
  function startSubTest() {
    $this->indent_();
  }

  /// \return void
  function endSubTest() {
    $this->unindent_();
  }

  function write($_value_) {
    return parent::write($this->formatMultiLine_('# ', $_value_));
  }
}

// }}} #############################################################################################

// {{{ TapHarnessOutStream

class TapHarnessOutStream extends Framework\StreamWriter implements Runner\TestHarnessOutStream {
  function writeResult($_name_, Framework\TestResult $_result_) {
      if ($_result_->passed) {
        $status = 'ok';
      } else {
        if ($_result_->bailedOut) {
          $status = 'BAILED OUT!';
        } else {
          $status = 'KO';
        }
      }

      if ($_result_->runtimeErrorCount > 0) {
        // There are runtime errors. See diagnostics above.
        $status .= ' DUBIOUS';
      }

      if (($dotlen = 40 - \strlen($_name_)) > 0) {
        $statusLine = $_name_ . \str_repeat('.', $dotlen) . ' ' . $status;
      } else {
        $statusLine = $_name_ . '... '. $status;
      }

      $this->writeLine($statusLine);

      if (!$_result_->passed) {
        $this->writeLine(
          \sprintf('Failed %s/%s subtests', $_result_->failuresCount, $_result_->testsCount));
      }
  }

  function writeSummary($_passed_, $_suites_count_, $_tests_count_) {
    if ($_passed_) {
      $this->writeLine('All tests successful.');
    }
    $this->writeLine(\sprintf('Test suites=%s, Tests=%s', $_suites_count_, $_tests_count_));
    $this->writeLine(\sprintf('Result: %s', ($_passed_ ? 'PASS' : 'FAIL')));
  }
}

// }}} #############################################################################################

// {{{ TapProducer

class TapProducer extends Framework\TestProducer {
  const
    SUCCESS_CODE = 0,
    FATAL_CODE   = 255;

  function __construct(TapOutStream $_outStream_, TapErrStream $_errStream_) {
    parent::__construct($_outStream_, $_errStream_);
  }

  protected function shutdownCore_() {
    $exit_code = $this->getExitCode_();

    exit($exit_code);
  }

  protected function getExitCode_() {
    if ($this->getRuntimeErrorsCount() > 0) {
      return self::FATAL_CODE;
    } else if ($this->passed()) {
      return self::SUCCESS_CODE;
    } else if ($this->bailedOut()) {
      return self::FATAL_CODE;
    } else if (($count = $this->getFailuresCount()) > 0) {
      return $count < self::FATAL_CODE ? $count : (self::FATAL_CODE - 1);
    } else {
      // Other kind of errors: extra tests, unattended interrupt.
      return self::FATAL_CODE;
    }
  }
}

final class DefaultTapProducer extends TapProducer {
  function __construct() {
    parent::__construct(new TapOutStream('php://stdout', \TRUE), new TapErrStream('php://stderr'));
  }
}

// }}} #############################################################################################

// EOF
