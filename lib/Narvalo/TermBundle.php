<?php

namespace Narvalo\Term;

require_once 'NarvaloBundle.php';
require_once 'Narvalo/IOBundle.php';

use \Narvalo;
use \Narvalo\IO;

// {{{ Ansi

final class Ansi {
  // The following list of ANSI codes is taken from:
  //  http://perldoc.perl.org/Term/ANSIColor.html
  // See also:
  //  http://en.wikipedia.org/wiki/ANSI_escape_code
  const
    // SGR (Select Graphic Rendition) parameters.
    Clear      = 0,
    Reset      = 0,   // Alias for Clear.
    Bold       = 1,
    Dark       = 2,
    Faint      = 2,   // Alias for Dark.
    Italic     = 3,
    Underline  = 4,
    Underscore = 4,   // Alias fot Underline.
    Blink      = 5,
    Reverse    = 7,
    Concealed  = 8,

    // Foreground colors.
    Black      = 30,
    Red        = 31,
    Green      = 32,
    Yellow     = 33,
    Blue       = 34,
    Magenta    = 35,
    Cyan       = 36,
    White      = 37,

    // Background colors.
    OnBlack    = 40,
    OnRed      = 41,
    OnGreen    = 42,
    OnYellow   = 43,
    OnBlue     = 44,
    OnMagenta  = 45,
    OnCyan     = 46,
    OnWhite    = 47
    ;

  // NB: The wired sequence ^[ found below is actually the ESC char.

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
      : \sprintf('[%sm%s[%dm', \join(';', $codes), $_value_, self::Reset);
  }

  static function Green() {
    static $_csi;
    if (\NULL === $_csi) {
      $_csi = Ansi::Color(Ansi::Green);
    }
    return $_csi;
  }

  static function Red() {
    static $_csi;
    if (\NULL === $_csi) {
      $_csi = Ansi::Color(Ansi::Red);
    }
    return $_csi;
  }

  static function Reset() {
    static $_csi;
    if (\NULL === $_csi) {
      $_csi = Ansi::Color(Ansi::Reset);
    }
    return $_csi;
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ StandardErrorLogger

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

    $this->_stream->writeLine(Ansi::Red() . $msg . Ansi::Reset());
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

// }}} ---------------------------------------------------------------------------------------------
// {{{ Command_

abstract class Command_ {
  const
    SuccessCode = 0,
    FailureCode = 1;

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

      $exit_code = (new static($argv))->run() ?: self::SuccessCode;
    } catch (\Exception $e) {
      static::OnUnhandledException_($e);
      $exit_code = static::FailureCode;
    }

    exit($exit_code);
  }

  abstract function run();

  protected static function CreateLogger_() {
    $logger = new Narvalo\AggregateLogger();
    $logger->attach(new Narvalo\DefaultLogger());
    $logger->attach(new StandardErrorLogger(Narvalo\Loggerlevel::Error));

    return $logger;
  }

  protected static function OnUnhandledException_(\Exception $_e_) {
    Narvalo\Log::Error($_e_);
  }
}

// }}} ---------------------------------------------------------------------------------------------

// EOF
