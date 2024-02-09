<?php
// namespace YeAPF;
/**
 * Pequeno conjunto de funções de primeiro nivel do YeAPF
 * Versão experimental
 * (c) 2004-2023 Esteban D.Dortta
 * https://yeapf.com
 * MIT Licensed
 */
include __DIR__ . '/yLogger.php';
include __DIR__ . '/yAnalyzer.php';
include __DIR__ . '/yLock.php';
include __DIR__ . '/yParser.php';
include __DIR__ . '/yDataFiller.php';

function _log($message)
{
  $aux = explode("\n", rtrim($message, "\n"));
  foreach ($aux as $line) {
    if (strlen(trim($line)) > 0)
      \YeAPF\yLogger::log(0, 0, $line);
  }
}

function _trace($message)
{
  $aux = explode("\n", rtrim($message, "\n"));
  foreach ($aux as $line) {
    if (strlen(trim($line)) > 0)
      \YeAPF\yLogger::trace(0, 0, $line);
  }
}
