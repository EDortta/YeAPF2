<?php

function _log($message)
{
  if (is_array($message)) {
    $aux = $message;
  } else {
    $aux = explode("\n", rtrim($message ?? '', "\n"));
  }
  foreach ($aux as $k => $line) {
    if (is_array($line)) {
      _log($line);
    } else {
      if (strlen(trim($line)) > 0) {
        if (is_numeric($k))
          \YeAPF\yLogger::log(0, YeAPF_LOG_DEBUG, $line);
        else {
          \YeAPF\yLogger::log(0, YeAPF_LOG_DEBUG, "$k : $line");
        }
      }
    }
  }
}

function _trace($message)
{
  if (is_array($message)) {
    $aux = $message;
  } else {
    $aux = explode("\n", rtrim($message ?? '', "\n"));
  }
  foreach ($aux as $k => $line) {
    if (is_array($line)) {
      _trace($line);
    } else {
      if (strlen(trim($line)) > 0) {
        if (is_numeric($k))
          \YeAPF\yLogger::trace(0, YeAPF_LOG_DEBUG, $line);
        else {
          \YeAPF\yLogger::trace(0, YeAPF_LOG_DEBUG, "$k : $line");
        }
      }
    }
  }
}
