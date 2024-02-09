<?php

function _log($message) {
    $aux = explode("\n", rtrim($message??'', "\n"));
    foreach ($aux as $line) {
      if (strlen(trim($line))>0)
        \YeAPF\yLogger::log(0, YeAPF_LOG_DEBUG, $line);
    }

  }

function _trace($message) {
    \YeAPF\yLogger::trace(0, YeAPF_LOG_DEBUG, $message);
}