<?php
class ExemploPlugin implements YeapfPlugin {
  public function initialize($domain, $gateway, &$contexto) {

    return true;
  }

  public function do($subject, $action, ...$params) {

  }
}
