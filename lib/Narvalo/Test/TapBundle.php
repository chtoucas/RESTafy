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

use Narvalo\Test\Framework;
use Narvalo\Test\Runner;
use Narvalo\Test\Tap\Internal as _;

define('CRLF_REGEX_PART',       '(?:\r|\n)+');
// RegEx to find any combination of \r and \n in a string.
define('CRLF_REGEX',            '{' . CRLF_REGEX_PART . '}');
// RegEx to find any combination of \r and \n at the end of a string.
define('TRAILING_CRLF_REGEX',   '{' . CRLF_REGEX_PART . '\z}s');
// RegEx to find any combination of \r and \n inside a normalized string.
define('MULTILINE_CRLF_REGEX',  '{' . CRLF_REGEX_PART . '(?!\z)}');

// {{{ TapOutStream

class TapOutStream extends _\TapStream implements Framework\TestOutStream {
  // FIXME: check the correct version in use.
  const Version = '13';

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
    return $this->writeLine_('TAP version ' . self::Version);
  }

  function writeFooter() {
    ;
  }

  function writePlan($_num_of_tests_) {
    return $this->writeLine_('1..' . $_num_of_tests_);
  }

  function writeSkipAll($_reason_) {
    return $this->writeLine_('1..0 skip ' . self::_FormatReason($_reason_));
  }

  function writeTestCase(Framework\DefaultTestCase $_test_, $_number_) {
    $desc = self::_FormatDescription($_test_->getDescription());
    $line = \sprintf('%s %d - %s', $_test_->passed() ? 'ok' : 'not ok', $_number_, $desc);
    return $this->writeLine_($line);
  }

  function writeTodoTestCase(Framework\TodoTestCase $_test_, $_number_) {
    $reason = self::_FormatReason($_test_->getReason());
    $line = \sprintf('ok %d # SKIP %s', $_number_, $reason);
    return $this->writeLine_($line);
  }

  function writeSkipTestCase(Framework\SkipTestCase $_test_, $_number_) {
    $desc   = self::_FormatDescription($_test_->getDescription());
    $reason = self::_FormatReason($_test_->getReason());
    $line = \sprintf('%s %d - %s # TODO %s',
      $_test_->passed() ? 'ok' : 'not ok', $_number_, $desc, $reason);
    return $this->writeLine_($line);
  }

  function writeBailOut($_reason_) {
    return $this->rawWrite_('Bail out! ' . self::_FormatReason($_reason_) . self::EOL);
  }

  function writeComment($_comment_) {
    if (!$this->_verbose) {
      return;
    }
    return $this->write_( $this->formatMultiLine_('# ', $_comment_) );
  }

  protected function cleanup_($_disposing_) {
    if (!$this->opened()) {
      return;
    }
    // FIXME Close workflow
    parent::cleanup_($_disposing_);
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
// {{{ TapErrStream

class TapErrStream extends _\TapStream implements Framework\TestErrStream {
  /// \return integer
  function startSubTest() {
    $this->indent_();
  }

  /// \return void
  function endSubTest() {
    $this->unindent_();
  }

  function write($_value_) {
    return $this->write_( $this->formatMultiLine_('# ', $_value_) );
  }

  protected function cleanup_($_disposing_) {
    if (!$this->opened()) {
      return;
    }
    parent::cleanup_($_disposing_);
  }
}

class DefaultTapErrStream extends TapErrStream {
  function __construct() {
    // FIXME STDERR
    parent::__construct('php://stderr');
  }
}

class InMemoryTapErrStream extends TapErrStream {
  function __construct() {
    parent::__construct('php://memory');
  }
}

// }}} #############################################################################################
// {{{ TapProducer

class TapProducer extends Framework\TestProducer {
  function __construct(TapOutStream $_outStream_, TapErrStream $_errStream_) {
    parent::__construct($_outStream_, $_errStream_);
  }
}

final class DefaultTapProducer extends TapProducer {
  function __construct() {
    parent::__construct(new DefaultTapOutStream(\TRUE), new DefaultTapErrStream());
  }
}

// }}} #############################################################################################
// {{{ TapRunner

final class TapRunner extends Runner\TestRunner {
  const
    SUCCESS_CODE = 0,
    FATAL_CODE   = 255;

  function __construct(TapProducer $_producer_ = \NULL) {
    parent::__construct($_producer_ ?: new DefaultTapProducer());
  }

  function runTest($_test_) {
    $hidden_errors_count = parent::runTest($_test_);

    $exit_code = $hidden_errors_count > 0 ? self::FATAL_CODE : $this->getExitCode_();

    $this->terminate_($exit_code);
  }

  protected function terminate_($_code_) {
    exit($_code_);
  }

  protected function getExitCode_() {
    $producer = $this->getProducer_();

    if ($producer->passed()) {
      // All tests passed and no abnormal error.
      $code = self::SUCCESS_CODE;
    } else if ($producer->bailedOut()) {
      $code = self::FATAL_CODE;
    } else if (($count = $producer->getFailuresCount()) > 0) {
      // There are failures.
      $code = $count < self::FATAL_CODE ? $count : (self::FATAL_CODE - 1);
    } else {
      // Other kind of errors: extra tests, unattended interrupt.
      $code = self::FATAL_CODE;
    }

    return $code;
  }
}

// }}} #############################################################################################

// {{{ Internal

namespace Narvalo\Test\Tap\Internal;

class TapStream {
  const EOL = "\n";

  private
    $_handle,
    $_indent = '',
    $_opened = \FALSE;

  function __construct($_path_) {
    // Open the handle
    $handle = \fopen($_path_, 'w');
    if (\FALSE === $handle) {
      throw new TestStreamException("Unable to open '{$_path_}' for writing");
    }
    $this->_opened = \TRUE;
    $this->_handle = $handle;
  }

  function __destruct() {
    $this->cleanup_(\FALSE);
  }

  function close() {
    $this->cleanup_(\TRUE);
  }

  function opened() {
    // XXX
    return $this->_opened;
  }

  function reset() {
    $this->_indent = '';
  }

  function canWrite() {
    // XXX
    return $this->_opened && 0 === \fwrite($this->_handle, '');
  }

  protected function cleanup_($_disposing_) {
    if (!$this->_opened) {
      return;
    }
    if (\TRUE === \fclose($this->_handle)) {
      $this->_opened = \FALSE;
    }
  }

  final protected function rawWrite_($_value_) {
      return \fwrite($this->_handle, $_value_);
  }

  protected function write_($_value_) {
    return \fwrite($this->_handle, $this->_indent . $_value_);
  }

  protected function writeLine_($_value_) {
    return \fwrite($this->_handle, $this->_indent . $_value_ . self::EOL);
  }

  protected function indent_() {
    $this->_indent = '    ' . $this->_indent;
  }

  protected function unindent_() {
    $this->_indent = \substr($this->_indent, 4);
  }

  protected static function FormatLine_($_prefix_, $_value_) {
    return $_prefix_ . \preg_replace(CRLF_REGEX, '', $_value_) . self::EOL;
  }

  protected function formatMultiLine_($_prefix_, $_value_) {
    $prefix = self::EOL . $this->_indent . $_prefix_;
    $value = \preg_replace(TRAILING_CRLF_REGEX, '', $_value_);

    return $_prefix_ . \preg_replace(MULTILINE_CRLF_REGEX, $prefix, $value) . self::EOL;
  }
}

// }}} #############################################################################################

// EOF
