<?php

namespace Narvalo\Term;

require_once 'NarvaloBundle.php';
require_once 'Narvalo/IOBundle.php';

use \Narvalo;
use \Narvalo\IO;

// {{{ FgColor

final class FgColor {
  const
    Black  = 30,
    Red    = 31,
    Green  = 32,
    Yellow = 33,
    Blue   = 34,
    Purple = 35,
    Cyan   = 36,
    Grey   = 37;

  private function __construct() { }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ BgColor

final class BgColor {
  const
    Black  = 40,
    Red    = 41,
    Green  = 42,
    Brown  = 43,
    Yellow = 43,
    Blue   = 44,
    Purple = 45,
    Cyan   = 46,
    Grey   = 47;

  private function __construct() { }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ Style

final class Style {
  const
    Normal     = 0,
    Bold       = 1,
    Dark       = 2,
    Underline  = 4,
    Blink      = 5,
    Reverse    = 6,
    Hidden     = 8;

  private function __construct() { }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{

class ColorizeOptions {
  public
    $fgColor,
    $bgColor,
    $style;
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ Colorize

final class Colorize {
  const Reset = "\033[0m";

  static function Foreground($_fgcolor_, $_value_) {
    return \sprintf("\033[%dm%s", $_fgcolor_, $_value_) . self::Reset;
  }

  static function Background($_bgcolor_, $_value_) {
    return \sprintf("\033[%dm%s", $_bgcolor_, $_value_) . self::Reset;
  }

  static function Apply(ColorizeOptions $_opts_, $_value_) {
    $codes = array();
    if (\NULL !== $_opts_->fgColor) {
      $codes[] = $_opts_->fgColor;
    }
    if (\NULL !== $_opts_->bgColor) {
      $codes[] = $_opts_->bgColor;
    }
    if (\NULL !== $_opts_->style) {
      $codes[] = $_opts_->style;
    }
    if (empty($code)) {
      return $_value_;
    }

    return \sprintf("\033[%sm%s", \implode(';', $codes), $_value_) . self::Reset;
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

    $this->_stream->writeLine(Colorize::Foreground(FgColor::Red, $msg));
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
