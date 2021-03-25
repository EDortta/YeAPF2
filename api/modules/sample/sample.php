<?php
class SamplePlugin extends YApiProducer
{
  private $domain;
  private $gateway;

  public function initialize($domain, $gateway, $contexto)
  {
    global $api;
    $this->$domain  = $domain;
    $this->$gateway = $gateway;
    _log("SamplePlugin configured to attend on $domain $gateway");
    $api->registerEntry(
      "serverTime",
      "GET",
      "samplePlugin/serverTime",
      null
    );

    $api->registerEntry(
      "getGeoIP",
      "GET",
      "samplePlugin/getGeoIP:ip",
      json_encode([
        "ip" => [
          "type" => "String",
        ],
      ]),
      false
    );

    return true;
  }

  function do($subject, $action, ...$params) {
    _log("SampleLogin doing $subject/$action");
  }

  public function serverTime()
  {
    $ret               = $this->emptyRet();
    $ret['http_code']  = 200;
    $ret['anyMessage'] = "Server time is " . date("Y-m-d H:i:s");
    return $ret;
  }

  public function geoFromIp($ip)
  {
    $ret = $this->emptyRet();
    return $ret;
  }
}
