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

define('_CRLF_REGEX_PART',       '(?:\r|\n)+');
// RegEx to find any combination of \r and \n in a string.
define('_CRLF_REGEX',            '{' . _CRLF_REGEX_PART . '}');
// RegEx to find any combination of \r and \n at the end of a string.
define('_TRAILING_CRLF_REGEX',   '{' . _CRLF_REGEX_PART . '\z}s');
// RegEx to find any combination of \r and \n inside a normalized string.
define('_MULTILINE_CRLF_REGEX',  '{' . _CRLF_REGEX_PART . '(?!\z)}');

// TAP streams.
// #################################################################################################

// FIXME: TapStream should be internal.
// {{{ TapStream

class TapStream extends Framework\FileStreamWriter {
  private $_indent = '';

  function reset() {
    $this->_indent = '';
  }

  function startSubTest() {
    $this->_indent();
  }

  function endSubTest() {
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
  const VERSION = '13';

  private $_verbose;

  function __construct($_path_, $_verbose_) {
    parent::__construct($_path_);

    $this->_verbose = $_verbose_;
  }

  function writeHeader() {
    return $this->writeTapLine_('TAP version ' . self::VERSION);
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

  function writeTestCase(Framework\TestCase $_test_, $_number_) {
    $desc = self::_FormatDescription($_test_->getDescription());
    $line = \sprintf('%s %d - %s', $_test_->passed() ? 'ok' : 'not ok', $_number_, $desc);
    return $this->writeTapLine_($line);
  }

  function writeTodoTestCase(Framework\TodoTestCase $_test_, $_number_) {
    $reason = self::_FormatReason($_test_->getReason());
    $line = \sprintf('ok %d # SKIP %s', $_number_, $reason);
    return $this->writeTapLine_($line);
  }

  function writeSkipTestCase(Framework\SkipTestCase $_test_, $_number_) {
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
      \trigger_error("The description '$_desc_' contains invalid chars.", \E_USER_NOTICE);
    }
    return $desc;
  }

  private static function _FormatReason($_reason_) {
    $reason = \preg_replace(_CRLF_REGEX, '¤', $_reason_);
    if ($reason != $_reason_) {
      \trigger_error("The reason '$_reason_' contains invalid chars.", \E_USER_NOTICE);
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

// TAP producers.
// #################################################################################################

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

// }}} ---------------------------------------------------------------------------------------------
// {{{ DefaultTapProducer

final class DefaultTapProducer extends TapProducer {
  function __construct($_verbose_) {
    parent::__construct(
      new TapOutStream('php://stdout', $_verbose_), new TapErrStream('php://stderr'));
  }
}

// }}} ---------------------------------------------------------------------------------------------

// TAP runners.
// #################################################################################################

// {{{ TapRunner

class TapRunner extends Runner\TestRunner {
  function __construct(TapProducer $_producer_) {
    parent::__construct($_producer_);
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ DefaultTapRunner

class DefaultTapRunner extends TapRunner {
  function __construct($_verbose_) {
    parent::__construct(new DefaultTapProducer($_verbose_));
  }
}

// }}} ---------------------------------------------------------------------------------------------

// TAP harnesses.
// #################################################################################################

// {{{ TapHarnessStream

final class TapHarnessStream
  extends Framework\FileStreamWriter
  implements Runner\TestHarnessStream
  {
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

      if ($_result_->runtimeErrorsCount > 0) {
        // There are runtime errors. See diagnostics above.
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
          'Suites=%s, Failures=%s',
          $_summary_->suitesCount,
          $_summary_->failedSuitesCount));
      $this->writeLine_(
        \sprintf(
          'Tests=%s, Failures=%s',
          $_summary_->testsCount,
          $_summary_->failedTestsCount));
      $this->writeLine_(\sprintf('Result: %s', ($_summary_->passed ? 'PASS' : 'FAIL')));
    }
  }

// }}}

// {{{ TapHarness

class TapHarness extends Runner\TestHarness {
  function __construct(TapHarnessStream $_stream_) {
    parent::__construct($_stream_);
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ DefaultTapHarness

final class DefaultTapHarness extends Runner\TestHarness {
  function __construct() {
    parent::__construct(new TapHarnessStream('php://stdout'));
  }
}

// }}} ---------------------------------------------------------------------------------------------

// EOF
