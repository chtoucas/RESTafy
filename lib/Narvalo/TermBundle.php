<?php

namespace Narvalo\Term;

require_once 'NarvaloBundle.php';
require_once 'Narvalo/IOBundle.php';

use \Narvalo;
use \Narvalo\IO;

final class Ansi {
  // The following list of ANSI codes is taken from:
  //  http://perldoc.perl.org/Term/ANSIColor.html
  // See also:
  //  http://en.wikipedia.org/wiki/ANSI_escape_code
  const
    // SGR (Select Graphic Rendition) parameters.
    CLEAR       = 0,
    RESET       = 0,   // Alias for Clear.
    BOLD        = 1,
    DARK        = 2,
    FAINT       = 2,   // Alias for Dark.
    ITALIC      = 3,
    UNDERLINE   = 4,
    UNDERSCORE  = 4,   // Alias fot Underline.
    BLINK       = 5,
    REVERSE     = 7,
    CONCEALED   = 8,

    // Foreground colors.
    BLACK       = 30,
    RED         = 31,
    GREEN       = 32,
    YELLOW      = 33,
    BLUE        = 34,
    MAGENTA     = 35,
    CYAN        = 36,
    WHITE       = 37,

    // Background colors.
    ON_BLACK    = 40,
    ON_RED      = 41,
    ON_GREEN    = 42,
    ON_YELLOW   = 43,
    ON_BLUE     = 44,
    ON_MAGENTA  = 45,
    ON_CYAN     = 46,
    ON_WHITE    = 47
    ;

  static function Color($_code_) {
    return \sprintf('[%dm', $_code_);
  }

  static function Colors() {
    return \sprintf('[%sm', \join(\func_get_args(), ';'));
  }

  static function Colorize($_value_) {
    $codes = \array_slice(\func_get_args(), 1);

    return empty($codes)
      ? $_value_
      : \sprintf('[%sm%s[%dm', \join(';', $codes), $_value_, self::RESET);
  }
}

class StandardErrorLogger extends Narvalo\Logger_ implements Narvalo\IDisposable {
  private
    $_stream,
    $_disposed = \FALSE;

  function __construct($_level_ = \NULL) {
    parent::__construct($_level_ ?: Narvalo\LoggerLevel::GetDefault());

    $this->_stream = IO\File::GetStandardError();
  }

  protected function log_($_level_, $_msg_) {
    $msg = \sprintf(
      '[%s] %s',
      Narvalo\LoggerLevel::ToString($_level_),
      $_msg_ instanceof \Exception ? $_msg_->getMessage() : $_msg_);

    $this->_stream->writeLine(Ansi::Colorize($msg, Ansi::RED));
  }

  function dispose() {
    $this->dispose_(\TRUE /* disposing */);
  }

  protected function dispose_($_disposing_) {
    if ($this->_disposed) {
      return;
    }

    if ($_disposing_) {
      if (\NULL !== $this->_stream) {
        $this->_stream->close();
      }
    }

    $this->_disposed = \TRUE;
  }
}

abstract class Cmd_ {
  const
    SUCCESS_CODE = 0,
    FAILURE_CODE = 1;

  private $_argv;

  protected function __construct(array $_argv_) {
    $this->_argv = $_argv_;
  }

  protected function getArgv_() {
    return $this->_argv;
  }

  static function Main() {
    global $argv;

    $exit_code;

    try {
      Narvalo\Log::SetLogger(static::CreateLogger_());

      $exit_code = (new static($argv))->run() ?: self::SUCCESS_CODE;
    } catch (\Exception $e) {
      static::OnUnhandledException_($e);
      $exit_code = static::FAILURE_CODE;
    }

    exit($exit_code);
  }

  abstract function run();

  protected static function CreateLogger_() {
    $logger = new Narvalo\AggregateLogger();
    $logger->attach(new Narvalo\DefaultLogger());
    $logger->attach(new StandardErrorLogger(Narvalo\Loggerlevel::ERROR));

    return $logger;
  }

  protected static function OnUnhandledException_(\Exception $_e_) {
    Narvalo\Log::Error($_e_);
  }
}

// EOF
