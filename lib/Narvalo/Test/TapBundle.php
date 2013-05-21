<?php
// Cf.
// - http://testanything.org/wiki/index.php/Main_Page
// - http://search.cpan.org/~petdance/Test-Harness-2.65_02/lib/Test/Harness/TAP.pod
// TAP specification
// - http://podwiki.hexten.net/TAP/TAP13.html?page=TAP13
// - http://podwiki.hexten.net/TAP/TAP.html?page=TAP

namespace Narvalo\Test\Tap;

require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/RunnerBundle.php';

use \Narvalo\Test\Framework;
use \Narvalo\Test\Runner;

define('_CRLF_REGEX_PART',       '(?:\r|\n)+');
// RegEx to find any combination of \r and \n in a string.
define('_CRLF_REGEX',            '{' . _CRLF_REGEX_PART . '}');
// RegEx to find any combination of \r and \n at the end of a string.
define('_TRAILING_CRLF_REGEX',   '{' . _CRLF_REGEX_PART . '\z}s');
// RegEx to find any combination of \r and \n inside a normalized string.
define('_MULTILINE_CRLF_REGEX',  '{' . _CRLF_REGEX_PART . '(?!\z)}');

// TAP streams
// =================================================================================================

// {{{ TapStream

class TapStream extends Framework\FileStreamWriter {
  // FIXME: TapStream should be internal.

  private $_indent = '';

  function reset() {
    $this->_indent = '';
  }

  function startSubtest() {
    $this->_indent();
  }

  function endSubtest() {
    $this->_unindent();
  }

  protected function writeTapLine_($_value_) {
    return $this->writeLine_($this->_indent . $_value_);
  }

  protected function formatMultiLine_($_prefix_, $_value_) {
    $prefix = \PHP_EOL . $this->_indent . $_prefix_;
    $value = \preg_replace(_TRAILING_CRLF_REGEX, '', $_value_);

    return $_prefix_ . \preg_replace(_MULTILINE_CRLF_REGEX, $prefix, $_value_);
  }

  protected function _indent() {
    $this->_indent = '    ' . $this->_indent;
  }

  protected function _unindent() {
    $this->_indent = \substr($this->_indent, 4);
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ TapOutStream

final class TapOutStream extends TapStream implements Framework\TestOutStream {
  const Version = 12;

  private $_verbose;

  function __construct($_path_, $_verbose_) {
    parent::__construct($_path_);

    $this->_verbose = $_verbose_;
  }

  function writeHeader() {
    if (self::Version > 12) {
      return $this->writeTapLine_(\sprintf('TAP version %s', self::Version));
    }
  }

  function writeFooter() {
    ;
  }

  function writePlan($_num_of_tests_) {
    return $this->writeTapLine_('1..' . $_num_of_tests_);
  }

  function writeSkipAll($_reason_) {
    return $this->writeTapLine_('1..0 skip ' . self::_FormatReason($_reason_));
  }

  function writeTestCaseResult(Framework\TestCaseResult $_test_, $_number_) {
    $desc = self::_FormatDescription($_test_->getDescription());
    $line = \sprintf('%s %d - %s', $_test_->passed() ? 'ok' : 'not ok', $_number_, $desc);
    return $this->writeTapLine_($line);
  }

  function writeTodoTestCaseResult(Framework\TodoTestCaseResult $_test_, $_number_) {
    $reason = self::_FormatReason($_test_->getReason());
    $line = \sprintf('ok %d # SKIP %s', $_number_, $reason);
    return $this->writeTapLine_($line);
  }

  function writeSkipTestCaseResult(Framework\SkipTestCaseResult $_test_, $_number_) {
    $desc   = self::_FormatDescription($_test_->getDescription());
    $reason = self::_FormatReason($_test_->getReason());
    $line = \sprintf('%s %d - %s # TODO %s',
      $_test_->passed() ? 'ok' : 'not ok', $_number_, $desc, $reason);
    return $this->writeTapLine_($line);
  }

  function writeBailOut($_reason_) {
    return $this->writeTapLine_('Bail out! ' . self::_FormatReason($_reason_));
  }

  function writeComment($_comment_) {
    if (!$this->_verbose) {
      return;
    }
    return $this->writeTapLine_($this->formatMultiLine_('# ', $_comment_));
  }

  private static function _FormatDescription($_desc_) {
    // Escape EOL.
    $desc = \preg_replace(_CRLF_REGEX, '¤', $_desc_);
    // Escape leading unsafe chars.
    $desc = \preg_replace('{^[\d\s]+}', '¤', $desc);
    // Escape #.
    $desc = \str_replace('#', '\\#', $desc);
    if ($desc != $_desc_) {
      \trigger_error(
        \sprintf('The description "%s" contains invalid chars.', $_desc_), \E_USER_NOTICE);
    }
    return $desc;
  }

  private static function _FormatReason($_reason_) {
    $reason = \preg_replace(_CRLF_REGEX, '¤', $_reason_);
    if ($reason != $_reason_) {
      \trigger_error(
        \sprintf('The reason "%s" contains invalid chars.', $_reason_), \E_USER_NOTICE);
    }
    return $reason;
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ TapErrStream

final class TapErrStream extends TapStream implements Framework\TestErrStream {
  function write($_value_) {
    return $this->writeTapLine_($this->formatMultiLine_('# ', $_value_));
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ TapHarnessStream

final class TapHarnessStream
  extends Framework\FileStreamWriter
  implements Runner\TestHarnessStream
  {
    function writeResult($_name_, Framework\TestSetResult $_result_) {
      if ($_result_->passed) {
        $status = 'ok';
      } else {
        if ($_result_->bailedOut) {
          $status = 'BAILED OUT!';
        } else {
          $status = 'KO';
        }
      }

      if ($_result_->runtimeErrorsCount > 0) {
        $status .= ' DUBIOUS';
      }

      if (($dotlen = 40 - \strlen($_name_)) > 0) {
        $statusLine = $_name_ . \str_repeat('.', $dotlen) . ' ' . $status;
      } else {
        $statusLine = $_name_ . '... '. $status;
      }

      $this->writeLine_($statusLine);

      if (!$_result_->passed) {
        $this->writeLine_(
          \sprintf('Failed %s/%s subtests', $_result_->failuresCount, $_result_->testsCount));
      }
    }

    function writeSummary(Runner\TestHarnessSummary $_summary_) {
      if ($_summary_->passed) {
        $this->writeLine_('All tests successful.');
      }
      $this->writeLine_(
        \sprintf(
          'Sets=%s, Failures=%s',
          $_summary_->setsCount,
          $_summary_->failedSetsCount));
      $this->writeLine_(
        \sprintf(
          'Tests=%s, Failures=%s',
          $_summary_->testsCount,
          $_summary_->failedTestsCount));
      $this->writeLine_(\sprintf('Result: %s', ($_summary_->passed ? 'PASS' : 'FAIL')));
    }
  }

// }}}

// TAP producer
// =================================================================================================

// {{{ TapProducer

class TapProducer extends Framework\TestProducer {
  const
    SuccessCode = 0,
    FailureCode = 255;

  function __construct(TapOutStream $_outStream_, TapErrStream $_errStream_) {
    parent::__construct($_outStream_, $_errStream_);
  }

  protected function shutdownCore_() {
    $exit_code = $this->getExitCode_();

    exit($exit_code);
  }

  protected function getExitCode_() {
    if ($this->getRuntimeErrorsCount() > 0) {
      return self::FailureCode;
    } elseif ($this->passed()) {
      return self::SuccessCode;
    } elseif ($this->bailedOut()) {
      return self::FailureCode;
    } elseif (($count = $this->getFailuresCount()) > 0) {
      return $count < self::FailureCode ? $count : (self::FailureCode - 1);
    } else {
      // Other kind of errors: extra tests, unattended interrupt.
      return self::FailureCode;
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------

// EOF
