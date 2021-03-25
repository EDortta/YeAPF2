<?php
class ExemploPlugin implements CronosPlugin
{
    public function initialize($domain, $gateway, $contexto) {

      return true;
    }

    public function do($subject, $action, ...$params) {

    }
}
