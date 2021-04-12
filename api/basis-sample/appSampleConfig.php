<?php
class SampleConfig extends YApiProducer {
  private $domain;
  private $gateway;

  public function initialize($domain, $gateway, &$contexto) {
    global $api;
    $this->$domain  = $domain;
    $this->$gateway = $gateway;

    $api->defineAPIName("NuoMed");
    $contexto['CFGDefaultLang'] = 'pt-br';

    return true;
  }

  function do($subject, $action, ...$params) {
  }

}
