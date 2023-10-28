<?php
declare (strict_types = 1);
namespace YeAPF;

define("YeAPF_INVALID_SPACE_NAME", 0x00000001);
define("YeAPF_METHOD_NOT_IMPLEMENTED", 0x00000002);

define("YeAPF_DATA_EXCEPTION_BASE", 0x00000100);
define("YeAPF_CONNECTION_BASE", 0x00000200);
define("YeAPF_COLLECTION_BASE", 0x00000300);
define("YeAPF_EYESHOT_BASE", 0x00000400);
define("YeAPF_SERVICE_BASE", 0x00000500);
define("YeAPF_SECURITY_BASE", 0x00000600);

require_once "yeapf-definitions.php";

function handleException($message, $code, $file, $line, $trace, $isException = true) {
  $ret = [
      'message' => str_replace("\t", "           ", $message),
      'code'    => $code,
      'file'    => $file,
      'line'    => $line,
      'trace'   => $trace,
  ];

  if (ob_get_length()) ob_clean();

  $dbgInfo = "";

  if (!YeAPFConsole::isTTY()) {
      $dbgInfo .= "<PRE>";
  }

  $width = YeAPFConsole::getWidth();
  $dbgInfo .= "\n" . str_repeat("=", $width) . "\n";
  if ($isException)
    $dbgInfo .= "EXCEPTION\n";
  else 
    $dbgInfo .= "ERROR\n";
  if ($ret['code'] ?? 0 > 0) {
      $dbgInfo .= "     CODE: " . dec2hex($ret['code']) . "\n";
  }

  $dbgInfo .= "  MESSAGE: " . trim(wordwrap("           " . $ret['message'], $width - 11, "\n           ")) . "\n";
  $dbgInfo .= "     FILE: " . substr($ret['file'] . ':' . $ret['line'], -$width + 11) . "\n";
  $dbgInfo .= "STACK TRC:\n";
  foreach ($ret['trace'] as $trace) {
    if (!empty($trace['file']))
      $dbgInfo .= "    " . substr($trace['file'] . ':' . $trace['line'], -$width + 11) . "\n";
  }
  $dbgInfo .= str_repeat("=", $width) . "\n";

  if (!YeAPFConsole::isTTY()) {
      $dbgInfo .= "</pre>";
  }

  echo $dbgInfo;
  _log($dbgInfo);
  if ($isException)
    exit($ret['code']);
}

class YeAPFException extends \Exception{
  function __construct(string $message, int $code, \Exception $previous = null) {
    $hex  = '';
    $from = '';

    if ($code) {
      $hex = "Error: " . dec2hex($code) . " ";
    }

    if ($previous) {
      $from = "From " . $previous->getFile() . ":" . $previous->getLine() . " ";
      if ($previous->getCode() > 0) {
        $from .= "Error: " . dec2hex($previous->getCode()) . " ";
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
