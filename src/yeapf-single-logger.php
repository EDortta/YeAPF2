<?php

function _log($message) {
    $aux = explode("\n", rtrim($message??'', "\n"));
    foreach ($aux as $line) {
      \YeAPF\yLogger::log(0, 0, $line);
    }

  }