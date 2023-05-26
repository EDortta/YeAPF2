<?php
// namespace YeAPF;
/**
 * Pequeno conjunto de funções de primeiro nivel do YeAPF
 * Versão experimental
 * (c) 2004-2020 Esteban D.Dortta
 * https://yeapf.com
 * MIT Licensed
 **/

include __DIR__ . "/yLogger.php";
include __DIR__ . "/yAnalyzer.php";
include __DIR__ . "/yLock.php";
include __DIR__ . "/yParser.php";

function _log($message) {
    $aux = explode("\n", rtrim($message,"\n"));
    foreach($aux as $line)
      \YeAPF\yLogger::log(0, 0, $line);
}