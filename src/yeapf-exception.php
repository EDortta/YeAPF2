<?php declare(strict_types=1);

namespace YeAPF;

global $__handlingException, $__runningUnderPHPUnit;
$__handlingException = false;
$__runningUnderPHPUnit = (YeAPFConsole::isTTY() && strpos($_SERVER['argv'][0], 'phpunit'));


function handleException($message, $code, $file, $line, $trace, $isException = true)
{
  global $__handlingException, $__runningUnderPHPUnit;

  if (!$__handlingException) {
    $__handlingException = true;
    $ret = [
      'message' => str_replace("\t", '           ', $message),
      'code' => $code,
      'file' => $file,
      'line' => $line,
      'trace' => $trace,
    ];

    if (ob_get_length())
      ob_clean();

    $dbgInfo = '';

    if (!YeAPFConsole::isTTY()) {
      $dbgInfo .= '<PRE>';
    }

    $width = YeAPFConsole::getWidth();
    $dbgInfo .= "\n" . str_repeat('=', $width) . "\n";
    if ($isException)
      $dbgInfo .= "EXCEPTION\n";
    else
      $dbgInfo .= "ERROR\n";
    if ($ret['code'] ?? 0 > 0) {
      $dbgInfo .= '      CODE: ' . dec2hex($ret['code']) . "\n";
    }

    $dbgInfo .= '   MESSAGE: ' . trim(wordwrap('           ' . $ret['message'], $width - 11, "\n           ")) . "\n";
    $dbgInfo .= '      FILE: ' . substr($ret['file'] . ':' . $ret['line'], -$width + 11) . "\n";
    $dbgInfo .= " STACK TRC:\n";
    foreach ($ret['trace'] as $trace) {
      if (!empty($trace['file']))
        $dbgInfo .= '    ' . substr($trace['file'] . ':' . $trace['line'], -$width + 11) . "\n";
    }

    if ($isException) {
      _trace($dbgInfo . str_repeat('=', $width));
    }

    $traceFilename = \YeAPF\yLogger::getTraceFilename();
    if ($traceFilename) {
      $dbgInfo .= "TRACE FILE: $traceFilename\n";
    }
    $dbgInfo .= str_repeat('=', $width) . "\n";

    if (!YeAPFConsole::isTTY()) {
      $dbgInfo .= '</pre>';
    }

    if (!$__runningUnderPHPUnit) {
      echo $dbgInfo;      
    }
    _log($dbgInfo);
    if ($isException) {
      if (!$__runningUnderPHPUnit) {
        \YeAPF\yLogger::closeTrace(true);
        \YeAPF\yLogger::closeLog();
        exit($ret['code'] ?? 0);
      }
    }
    $__handlingException = false;
  }
}

class YeAPFException extends \Exception
{
  function __construct(string $message, int $code = YeAPF_UNDEFINED_EXCEPTION, \Exception $previous = null)
  {
    $hex = '';
    $from = '';

    if ($code) {
      $hex = 'Error: ' . dec2hex($code) . ' ';
    }

    if ($previous) {
      $from = 'From ' . $previous->getFile() . ':' . $previous->getLine() . ' ';
      if ($previous->getCode() > 0) {
        $from .= 'Error: ' . dec2hex($previous->getCode()) . ' ';
      }
    }
    $finalMessage = $hex . $from . $message;
    _log($finalMessage);

    $file = $this->getFile();
    $line = $this->getLine();
    $trace = $this->getTrace();
    handleException($finalMessage, $code, $file, $line, $trace, true);

    parent::__construct($finalMessage, $code);
  }
}

if (!$__runningUnderPHPUnit) {
  set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    $message = "PHP Error: $errstr";
    $code = $errno;
    $file = $errfile;
    $line = $errline;
    $trace = debug_backtrace();

    handleException($message, $code, $file, $line, $trace, false);
  });

  set_exception_handler(function ($exception) {
    $message = $exception->getMessage();
    $code = $exception->getCode();
    $file = $exception->getFile();
    $line = $exception->getLine();
    $trace = $exception->getTrace();

    handleException($message, $code, $file, $line, $trace);
  });
}