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
    $this->writeTapLine_(
      self::_GetTestLine($_test_->passed(), $_number_, $_test_->getDescription()));
  }

  function writeAlteredTestCaseResult(Framework\AlteredTestCaseResult $_test_, $_number_) {
    $line = self::_GetTestLine($_test_->passed(), $_number_, $_test_->getDescription());
    $reason = self::_FormatReason($_test_->getAlterationReason());
    $this->writeTapLine_(\sprintf('%s # %s %s', $line, $_test_->getAlterationName(), $reason));
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

  // Private methods
  // ---------------

  private static function _GetTestLine($_passed_, $_number_, $_desc_) {
    $status = $_passed_ ? 'ok' : 'not ok';
    if ('' !== $_desc_) {
      $line = \sprintf('%s %d - %s', $status, $_number_, self::_FormatDescription($_desc_));
    } else {
      $line = \sprintf('%s %d', $status, $_number_);
    }
    return $line;
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

  function close() {
    $this->dispose();
  }

  function writeResult($_name_, Framework\TestSetResult $_result_) {
    $diagnostic = '';

    if ($_result_->bailedOut()) {
      $status = 'BAILED OUT!';
    } else if ($_result_->passed()) {
      $status = 'ok' . ($_result_->getRuntimeErrorsCount() > 0 ? ' DUBIOUS' : '');
    } else {
      $status = 'KO';
    }

    if (!$_result_->passed() && ($tests_count = $_result_->getTestsCount()) > 0) {
      $diagnostic = \sprintf(
        'Failed %s/%s of tests run', $_result_->getFailuresCount(), $tests_count);
    }

    if (($dotlen = 50 - \strlen($_name_)) > 0) {
      $statusLine = $_name_ . \str_repeat('.', $dotlen) . ' ' . $status;
    } else {
      $statusLine = $_name_ . '... '. $status;
    }

    $this->_stream->writeLine($statusLine);
    if ('' !== $diagnostic) {
      $this->_stream->writeLine($diagnostic);
    }
  }

  function writeSummary(Runner\TestHarnessSummary $_summary_) {
    $this->_stream->writeLine('-----------------------');

    if ($_summary_->passed()) {
      $this->_stream->writeLine('All tests successful');
      $this->_stream->writeLine(\sprintf(
        'Sets=%s, Tests=%s', $_summary_->getSetsCount(), $_summary_->getTestsCount()));
      $this->_writeWarning($_summary_);
      $this->_stream->writeLine('Result: PASS');
    } else {
      $this->_stream->writeLine(\sprintf(
        'Failed %s/%s (%s/%s) of test sets (units) run',
        $_summary_->getFailedSetsCount(),
        $_summary_->getSetsCount(),
        $_summary_->getFailedTestsCount(),
        $_summary_->getTestsCount()));
      $this->_writeWarning($_summary_);
      $this->_stream->writeLine('Result: FAIL');
    }
  }

  protected function close_() {
    if (\NULL !== $this->_stream) {
      $this->_stream->close();
    }
  }

  private function _writeWarning(Runner\TestHarnessSummary $_summary_) {
    if (($dubious_count = $_summary_->getDubiousSetsCount()) > 0) {
      $dubious_count > 1
        ? $this->_stream->writeLine('WARNING: There is one dubious set')
        : $this->_stream->writeLine(\sprintf('WARNING: There are %s dubious sets', $dubious_count));
    }
  }
}

// }}} ---------------------------------------------------------------------------------------------

// EOF
