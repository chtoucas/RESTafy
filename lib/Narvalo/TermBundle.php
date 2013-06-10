<?php

namespace Narvalo\Term;

require_once 'NarvaloBundle.php';
require_once 'Narvalo/IOBundle.php';

require_once '_Aliens/Color2.php';

use \Narvalo;
use \Narvalo\IO;

// {{{ StandardErrorLogger

class StandardErrorLogger extends Narvalo\Logger_ implements Narvalo\IDisposable {
  private
    $_color,
    $_stream,
    $_disposed = \FALSE;

  function __construct($_level_ = \NULL) {
    parent::__construct($_level_ ?: Narvalo\LoggerLevel::GetDefault());

    $this->_stream = IO\File::GetStandardError();
    $this->_color  = new \Console_Color2();
  }

  protected function log_($_level_, $_msg_) {
    $msg = \sprintf(
      '[%s] %s',
      Narvalo\LoggerLevel::ToString($_level_),
      $_msg_ instanceof \Exception ? $_msg_->getMessage() : $_msg_);
    $this->_stream->writeLine($this->_color->convert("%r$msg%n"));
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
