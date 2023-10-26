<?php

function _log($message) {
    $aux = explode("\n", rtrim($message??'', "\n"));
    foreach ($aux as $line) {
      if (strlen(trim($line))>0)
        \YeAPF\yLogger::log(0, 0, $line);
    }

  }