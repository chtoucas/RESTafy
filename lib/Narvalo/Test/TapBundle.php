<?php
// Cf.
// - http://testanything.org/wiki/index.php/Main_Page
// - http://search.cpan.org/~petdance/Test-Harness-2.65_02/lib/Test/Harness/TAP.pod
// TAP specification
// - http://podwiki.hexten.net/TAP/TAP13.html?page=TAP13
// - http://podwiki.hexten.net/TAP/TAP.html?page=TAP

namespace Narvalo\Test\Tap;

require_once 'NarvaloBundle.php';
require_once 'Narvalo/IOBundle.php';
require_once 'Narvalo/Test/FrameworkBundle.php';
require_once 'Narvalo/Test/RunnerBundle.php';

use \Narvalo;
use \Narvalo\IO;
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

class TapStream extends Narvalo\DisposableObject {
  // FIXME: TapStream should be internal.

  private
    $_writer,
    $_indent = '';

  function __construct(IO\TextWriter $_writer_) {
    $this->_writer = $_writer_;
  }

  function close() {
    $this->dispose();
  }

  function reset() {
    $this->_indent = '';
  }

  function startSubtest() {
    $this->_indent();
  }

  function endSubtest() {
    $this->_unindent();
  }

  protected function dispose_() {
    if (\NULL !== $this->_writer) {
      $this->_writer->close();
    }
  }

  protected function writeTapLine_($_value_) {
    $this->_writer->writeLine($this->_indent . $_value_);
  }

  protected function formatMultiLine_($_prefix_, $_value_) {
    $prefix = $this->_writer->getEndOfLine() . $this->_indent . $_prefix_;
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

final class TapOutStream extends TapStream implements Framework\ITestOutStream {
  const Version = 12;

  private $_verbose;

  function __construct(IO\TextWriter $_writer_, $_verbose_) {
    parent::__construct($_writer_);

    $this->_verbose = $_verbose_;
  }

  static function GetDefault() {
    return new self(IO\TextWriter::GetStandardOutput(), \TRUE);
  }

  function writeHeader() {
    if (self::Version > 12) {
      $this->writeTapLine_(\sprintf('TAP version %s', self::Version));
    }
  }

  function writeFooter() {
    ;
  }

  function writePlan($_num_of_tests_) {
    $this->writeTapLine_('1..' . $_num_of_tests_);
  }

  function writeSkipAll($_reason_) {
    $this->writeTapLine_('1..0 skip ' . self::_FormatReason($_reason_));
  }

  function writeTestCaseResult(Framework\TestCaseResult $_test_, $_number_) {
    $status = $_test_->passed() ? 'ok' : 'not ok';
    if ('' !== ($desc = $_test_->getDescription())) {
      $line = \sprintf('%s %d - %s', $status, $_number_, self::_FormatDescription($desc));
    } else {
      $line = \sprintf('%s %d', $status, $_number_);
    }
    $this->writeTapLine_($line);
  }

  function writeAlteredTestCaseResult(Framework\AlteredTestCaseResult $_test_, $_number_) {
    $reason = self::_FormatReason($_test_->getAlterationReason());
    if ('' !== ($desc = $_test_->getDescription())) {
      $line = \sprintf('ok %d - %s # %s %s',
        $_number_, self::_FormatDescription($desc), $_test_->getAlterationName(), $reason);
    } else {
      $line = \sprintf('ok %d # %s %s', $_number_, $_test_->getAlterationName(), $reason);
    }
    $this->writeTapLine_($line);
  }

  function writeBailOut($_reason_) {
    $this->writeTapLine_('Bail out! ' . self::_FormatReason($_reason_));
  }

  function writeComment($_comment_) {
    if (!$this->_verbose) {
      return;
    }
    $this->writeTapLine_($this->formatMultiLine_('# ', $_comment_));
  }

  private static function _FormatDescription($_desc_) {
    // Escape EOL.
    $desc = \preg_replace(_CRLF_REGEX, '¤', $_desc_);
    // Escape leading unsafe chars.
    $desc = \preg_replace('{^\s+}', '¤', $desc);
    // Escape #.
    $desc = \str_replace('#', '\\#', $desc);
    if ($desc != $_desc_) {
      Narvalo\Log::Notice(
        \sprintf('The description "%s" contains invalid chars.', $_desc_), \E_USER_NOTICE);
    }
    return $desc;
  }

  private static function _FormatReason($_reason_) {
    $reason = \preg_replace(_CRLF_REGEX, '¤', $_reason_);
    if ($reason != $_reason_) {
      Narvalo\Log::Notice(
        \sprintf('The reason "%s" contains invalid chars.', $_reason_), \E_USER_NOTICE);
    }
    return $reason;
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ TapErrStream

final class TapErrStream extends TapStream implements Framework\ITestErrStream {
  protected $_disposed = \FALSE;

  static function GetDefault() {
    return new self(IO\TextWriter::GetStandardOutput());
  }

  function write($_value_) {
    if ($this->_disposed) {
      // We want to make sure we do not lose any error message.
      Narvalo\Log::Error($_value_);
    } else {
      $this->writeTapLine_($this->formatMultiLine_('# ', $_value_));
    }
  }

  protected function free_() {
    $this->_disposed = \TRUE;
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ TapHarnessStream

final class TapHarnessStream implements Runner\ITestHarnessStream {
  private
    $_writer,
    $_indent = '';

  function __construct(IO\TextWriter $_writer_) {
    $this->_writer = $_writer_;
  }

  static function GetDefault() {
    return new self(IO\TextWriter::GetStandardOutput());
  }

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

    $this->_writer->writeLine($statusLine);

    if (!$_result_->passed) {
      $this->_writer->writeLine(
        \sprintf('Failed %s/%s subtests', $_result_->failuresCount, $_result_->testsCount));
    }
  }

  function writeSummary(Runner\TestHarnessSummary $_summary_) {
    if ($_summary_->passed) {
      $this->_writer->writeLine('All tests successful.');
    }
    $this->_writer->writeLine(
      \sprintf(
        'Sets=%s, Failures=%s',
        $_summary_->setsCount,
        $_summary_->failedSetsCount));
    $this->_writer->writeLine(
      \sprintf(
        'Tests=%s, Failures=%s',
        $_summary_->testsCount,
        $_summary_->failedTestsCount));
    $this->_writer->writeLine(\sprintf('Result: %s', ($_summary_->passed ? 'PASS' : 'FAIL')));
  }
}

// }}}

// TAP producer
// =================================================================================================

// {{{ TapProducer

class TapProducer extends Framework\TestProducer {
  const
    SuccessCode = 0,
    // NB: TAP expects 255 but this is a reserved code for PHP.
    FailureCode = 254;

  function __construct(TapOutStream $_outStream_, TapErrStream $_errStream_) {
    parent::__construct($_outStream_, $_errStream_);
  }

  static function GetDefault() {
    return new self(TapOutStream::GetDefault(), TapErrStream::GetDefault());
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
