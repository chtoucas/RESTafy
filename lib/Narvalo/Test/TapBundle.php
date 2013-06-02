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

// {{{ TapWriter

class TapWriter extends Narvalo\DisposableObject {
  private
    $_stream,
    $_indent = '';

  function __construct(IO\FileStream $_stream_) {
    $this->_stream = $_stream_;
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

  protected function close_() {
    if (\NULL !== $this->_stream) {
      $this->_stream->close();
    }
  }

  protected function writeTapLine_($_value_) {
    $this->_stream->writeLine($this->_indent . $_value_);
  }

  protected function formatMultiLine_($_prefix_, $_value_) {
    $prefix = $this->_stream->getEndOfLine() . $this->_indent . $_prefix_;
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

// {{{ TapOutWriter

final class TapOutWriter extends TapWriter implements Framework\ITestOutWriter {
  const Version = 12;

  private $_verbose;

  function __construct(IO\FileStream $_stream_, $_verbose_) {
    parent::__construct($_stream_);

    $this->_verbose = $_verbose_;
  }

  static function CreateDefault() {
    return new self(IO\File::GetStandardOutput(), \TRUE);
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
      Narvalo\Log::Notice(\sprintf('The description "%s" contains invalid chars.', $_desc_));
    }
    return $desc;
  }

  private static function _FormatReason($_reason_) {
    $reason = \preg_replace(_CRLF_REGEX, '¤', $_reason_);
    if ($reason != $_reason_) {
      Narvalo\Log::Notice(\sprintf('The reason "%s" contains invalid chars.', $_reason_));
    }
    return $reason;
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ TapErrWriter

final class TapErrWriter extends TapWriter implements Framework\ITestErrWriter {
  static function CreateDefault() {
    return new self(IO\File::GetStandardOutput());
  }

  function write($_value_) {
    $this->writeTapLine_($this->formatMultiLine_('# ', $_value_));
  }
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ TapHarnessWriter

final class TapHarnessWriter extends Narvalo\DisposableObject implements Runner\ITestHarnessWriter {
  private
    $_stream,
    $_indent = '';

  function __construct(IO\FileStream $_stream_) {
    $this->_stream = $_stream_;
  }

  static function CreateDefault() {
    return new self(IO\File::GetStandardOutput());
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

    $this->_stream->writeLine($statusLine);

    if (!$_result_->passed) {
      $this->_stream->writeLine(
        \sprintf('Failed %s/%s subtests', $_result_->failuresCount, $_result_->testsCount));
    }
  }

  function writeSummary(Runner\TestHarnessSummary $_summary_) {
    if ($_summary_->passed) {
      $this->_stream->writeLine('All tests successful.');
    }
    $this->_stream->writeLine(
      \sprintf(
        'Sets=%s, Failures=%s',
        $_summary_->setsCount,
        $_summary_->failedSetsCount));
    $this->_stream->writeLine(
      \sprintf(
        'Tests=%s, Failures=%s',
        $_summary_->testsCount,
        $_summary_->failedTestsCount));
    $this->_stream->writeLine(\sprintf('Result: %s', ($_summary_->passed ? 'PASS' : 'FAIL')));
  }

  protected function close_() {
    if (\NULL !== $this->_stream) {
      $this->_stream->close();
    }
  }
}

// }}}

// TAP producer
// =================================================================================================

// {{{ TapProducer

class TapProducer extends Framework\TestProducer {
  const
    SuccessCode = 0,
    // NB: TAP uses 255 but in PHP this is a reserved code.
    FailureCode = 254;

  function __construct(TapOutWriter $_outWriter_, TapErrWriter $_errWriter_) {
    parent::__construct($_outWriter_, $_errWriter_);
  }

  static function CreateDefault() {
    return new self(TapOutWriter::CreateDefault(), TapErrWriter::CreateDefault());
  }

  protected function stopCore_() {
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
