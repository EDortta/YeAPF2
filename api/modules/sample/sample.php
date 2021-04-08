<?php
class SamplePlugin extends YApiProducer {
  private $domain;
  private $gateway;

  public function initialize($domain, $gateway, $contexto) {
    global $api;
    $this->$domain  = $domain;
    $this->$gateway = $gateway;
    _log("SamplePlugin configured to attend on $domain $gateway");

    // sample 1 - get server time
    $api->registerEntry(
      "serverTime",
      "GET",
      "samplePlugin/serverTime",
      null
    );

    // sample 2 - query an external server geoip with cache
    $api->registerEntry(
      "getGeoIP",
      "GET",
      "samplePlugin/getGeoIP/:ip",
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

  public function serverTime() {
    $ret               = $this->emptyRet();
    $ret['http_code']  = 200;
    $ret['anyMessage'] = "Server time is " . date("Y-m-d H:i:s");
    return $ret;
  }

  public function getGeoIP($ip) {
    // by default we supose it will be a 200 (OK) return code
    $ret = $this->emptyRet(200);

    // we would like to use a cache in order to void repetitive request
    $cacheLocation = $this->grantCacheFolder(".geoLocation");

    if (!is_writable($cacheLocation)) {
      $ret['http_code'] = 507;
      $ret['error_msg'] = "$cacheLocation cannot be written";
    }

    if ($ret['http_code'] == 200) {
      $ret['cached'] = false;

      if (file_exists($cacheLocation . "/$ip.json")) {
        /**
         * if the answer is in cache, use it
         */
        $ret['cached'] = true;
        $ret['response'] = json_decode(file_get_contents($cacheLocation . "/$ip.json"),true);

      } else {
        /**
         * This API is an open one reachable at https://freegeoip.app/
         */

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL            => "https://freegeoip.app/json/$ip",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING       => "",
          CURLOPT_MAXREDIRS      => 10,
          CURLOPT_TIMEOUT        => 30,
          CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST  => "GET",
          CURLOPT_HTTPHEADER     => array(
            "accept: application/json",
            "content-type: application/json",
          ),
        ));

        $response = curl_exec($curl);
        $err      = curl_error($curl);

        curl_close($curl);

        if ($err) {
          $ret['error'] = $err;
        } else {
          if (substr($response,0,3)!='404') {
            /**
             * Save in cache file
             */
            file_put_contents($cacheLocation . "/$ip.json", $response);
          }
          $ret['response'] = json_decode($response,true);
          
        }
      }

    }

    return $ret;
  }
}
