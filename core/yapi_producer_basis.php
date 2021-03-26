<?php
require_once __DIR__ . "/yapi_producer_defines.php";

abstract class YApiProducer implements YeapfPlugin
{

  public function initialize($domain, $gateway, $contexto)
  {

  }

  function do($subject, $action, ...$params) {

  }

  public function emptyRet($http_code = 204, $error_msg = "")
  {
    return [
      "http_code"  => $http_code,
      "error_msg"  => $error_msg,
      "error_code" => 0,
    ];
  }

}

class YApiProducerBasis extends YApiProducer
{

  private $entryPoints  = [];
  private $classAliases = [];
  private $apiName      = 'Generic API';

  public function __construct()
  {
    $this->registerEntry("export", "GET", "/export", '{ "applicability": "Integer" }', true);
  }

  public function defineAPIName($apiName)
  {
    $this->apiName = $apiName;
  }

  public function defineClassAlias($classAlias)
  {
    $trace                            = debug_backtrace();
    $caller                           = $trace[1];
    $callerClass                      = isset($caller['class']) ? $caller['class'] : '';
    $this->classAliases[$callerClass] = $classAlias;
  }

  public function registerEntry(
    $functionName,
    $desiredMethod,
    $path,
    $params,
    $allowEmptyParams = false,
    $applicability = API_DEFAULT) {
    $trace       = debug_backtrace();
    $caller      = $trace[1];
    $callerClass = isset($caller['class']) ? $caller['class'] : '';
    $callerFile  = basename($caller['file']);

    if (substr($path, 0, 1) != '/') {
      $path = "/$path";
    }

    if (empty($params)) {
      $allowEmptyParams = true;
    }

    $endpointPath = $path;
    preg_match_all('/:([a-zA-Z0-9_]*)/', $path, $auxHiddenParams);

    foreach ($auxHiddenParams[0] as $key => $value) {
      $path = str_replace($value, "([a-zA-Z0-9\-\._@<>]*)", $path);
    }

    $path = str_replace("/", '\/', $path);

    $methodTag = '_' . mb_strtolower($desiredMethod) . '_';
    if (!isset($this->entryPoints[$path])) {
      $this->entryPoints[$path] = array();
    }

    _log("Registering $desiredMethod : $path");

    $this->entryPoints[$path][$methodTag] = array(
      '_method'           => mb_strtolower($desiredMethod),
      '_functionName'     => $functionName,
      '_params'           => json_decode($params, true),
      '_path'             => $endpointPath,
      '_allowEmptyParams' => $allowEmptyParams,
      '_applicability'    => $applicability,
      '_callerClass'      => $callerClass,
      '_callerFile'       => $callerFile,
    );
  }

  public function execute()
  {

    global $helpMode, $helpMap;

    // die(print_r($this->entryPoints));
    $method    = mb_strtolower($_SERVER['REQUEST_METHOD']);
    $methodTag = '_' . $method . '_';
    if (isset($_REQUEST['entrypoint'])) {
      $entrypoint = $_REQUEST['entrypoint'];
    } else {
      $entrypoint = "/status";
    }
    if (substr($entrypoint, 0, 1) != '/') {
      $entrypoint = "/$entrypoint";
    }

    $path = explode("/", trim($entrypoint, "/"));
    _log($entrypoint);

    if ($helpMap) {
      echo count($path) . " steps\n";
    }

    $entryPointParts = array();
    $entryPointFound = false;
    $inlineParamValues = [];

    foreach ($this->entryPoints as $path => $pathDefinition) {
      if (!$entryPointFound) {
        if ($helpMap) {
          _log("Comparing $path vs $entrypoint");
          echo __LINE__ . ": $path  vs  $entrypoint\n";
        }

        preg_match_all("/" . $path . "/", "$entrypoint", $entryPointPartsAux);
        if (isset($entryPointPartsAux[0][0])) {
          if ($entryPointPartsAux[0][0]>'') {
            if ($helpMap) {
              _log("match: ".print_r($entryPointPartsAux, true));
            }
            for($i=1; $i<count($entryPointPartsAux); $i++)  {
              _log("param #". ($i-1). ' ' . $entryPointPartsAux[$i][0]);
              $inlineParamValues[$i-1] = $entryPointPartsAux[$i][0];
            }
            $entryPointFound       = true;
            $currentEntrypointRoot = $this->entryPoints[$path];
            $entryPointParts       = $entryPointPartsAux;
            _log("EntryPoint: ".print_r($path, true));
          }
        }
      }
    }

    if ($helpMap) {
      echo $entryPointFound ? "EntryPoint found\n" : "EntryPoint not found\n";
    }

    if (isset($entryPointFound) && $entryPointFound) {
      if (isset($currentEntrypointRoot[$methodTag])) {
        $currentEntrypointRoot = &$currentEntrypointRoot[$methodTag];
      }
      _log('currentEntrypointRoot  = ' . json_encode($currentEntrypointRoot));

      if (isset($currentEntrypointRoot["_post_"])) {
        $currentEntrypointRoot = $currentEntrypointRoot["_post_"];
      }

      _log('currentEntrypointRoot[_METHOD]  = ' . json_encode($currentEntrypointRoot));
      _log(' method   = ' . $method);
      if ($currentEntrypointRoot['_method'] == $method) {

        $originalPath = $currentEntrypointRoot['_path'];
        _log("Extracting inline param names from $originalPath");
        preg_match_all('/:([a-zA-Z0-9_@.]*)/', $originalPath, $auxInlineParams);

        $inlineParams = array();
        for ($i = 0; $i < count($auxInlineParams[1]); $i++) {
          $inlineParamName = $entryPointParts[$i + 1][0];
          _log("inline param ".$auxInlineParams[1][$i]." value = $inlineParamName");
          // $inlineParams[$auxInlineParams[1][$i]] = $inlineParamName;
          $inlineParams[$auxInlineParams[1][$i]] = $inlineParamValues[$i];
        }

        $_PUT      = array();
        $_PUT_INFO = array();
        $_INPUT    = @file_get_contents("php://input");
        parse_str($_INPUT, $_PUT_INFO);
        /**
         * body pode ser JSON
         * então precisamos desmembrar isso
         **/
        if (count($_PUT_INFO) == 1) {
          reset($_PUT_INFO);
          $auxKey = key($_PUT_INFO);
          //print_r($auxKey);
          if (substr($auxKey, 0, 1) == '{') {
            try {
              $_PUT = json_decode($_INPUT, true);
            } catch (Exception $e) {
              _log("Warning! json cannot be decoded");
            }
          }
        }

        if ($method == 'put') {

          /* PUT data comes in on the stdin stream */
          $putdata      = fopen("php://input", "r");
          $tempFileName = tempnam(sys_get_temp_dir(), "PUT");

          $fp = fopen($tempFileName, "w");

          while ($data = fread($putdata, 1024)) {
            fwrite($fp, $data);
          }

          fclose($fp);
          fclose($putdata);

          $_PUT_DATA = file_get_contents($tempFileName);
          //die($_PUT_DATA);
          $endOfHead   = strpos($_PUT_DATA, "\r\n\r\n");
          $_PUT_HEADER = preg_split('#; |\r\n#', substr($_PUT_DATA, 0, $endOfHead));
          $fileTag     = @$_PUT_HEADER[0];
          if ($fileTag > "") {
            $fileContent = substr($_PUT_DATA, $endOfHead + 4);
            $fileContent = substr($fileContent, 0, strpos($fileContent, "$fileTag"));
            unlink($tempFileName);
            file_put_contents($tempFileName, $fileContent);
            $_PUT_INFO['temp_filename'] = $tempFileName;
          }
          _log("PUT header = " . json_encode($_PUT_HEADER));
          foreach ($_PUT_HEADER as $key => $value) {
            if (strpos($value, "=") > 0) {
              $value = explode("=", $value);
              _log("PUT_HEADER[key] = " . json_encode($value));
              $_PUT_INFO[$value[0]] = str_replace('"', "", $value[1]);
            } else if (strpos($value, "Content-Type") == 0) {
              $value = explode(":", $value);
              if (isset($value[1])) {
                $_PUT_INFO['Content-Type'] = $value[1];
                _log("value = " . json_encode($value));
                $aux = explode("/", $value[1]);
                if (isset($aux[1])) {
                  $_PUT_INFO['type']      = $aux[0];
                  $_PUT_INFO['extension'] = $aux[1];

                }
              }
            }
          }

          if (isset($_PUT_INFO['name'])) {

            $_PUT = array($_PUT_INFO['name'] => array(
              'filename'      => $_PUT_INFO['filename'],
              // 'type'          => $_PUT_INFO['type'],
              'temp_filename' => $_PUT_INFO['temp_filename'],
              'Content-Type'  => $_PUT_INFO['Content-Type'],
            ),
            );

          } else {
            $_PUT_INFO = array();
          }
        }
        parse_str(substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'] . '?', '?') + 1), $query_string);
        $params        = [];
        $paramNotFound = false;
        $notFoundList  = '';
        _log("PUT = " . json_encode($_PUT));
        _log('post params  = ' . json_encode($_REQUEST));
        if (!empty($currentEntrypointRoot['_params'])) {
          foreach ($currentEntrypointRoot['_params'] as $key => $value) {
            if ($helpMap) {
              _log(" \-- param $key = ".json_encode($value));
            }
            $aParam = (
              isset($_FILES[$key]) ? $_FILES[$key] :
              (
                isset($_REQUEST[$key]) ? $_REQUEST[$key] :
                (
                  isset($query_string[$key]) ? $query_string[$key] :
                  (
                    isset($_PUT[$key]) ? $_PUT[$key] : (
                      isset($inlineParams[$key]) ? $inlineParams[$key] :
                      '_undefined_')))));

            _log("key $key = [ " .(is_array($aParam) ? json_encode($aParam) : $aParam). " ]");

            if ($key == 'avatar_file' && isset($_FILES['file'])) {
              $aParam = $_FILES["file"];
            }

            if ($aParam == '_undefined_') {
              $paramNotFound = true;
              $notFoundList .= '(' . $key . ') ';
            }

            $params[count($params)] = $aParam;
          }}
        _log('params = ' . json_encode($params));
        _log("FILES = " . json_encode($_FILES));

        if ($paramNotFound && !$currentEntrypointRoot['_allowEmptyParams']) {
          _log("At least one parameter is missing");
          $ret                 = $this->emptyRet(406, 'At least one parameter is empty');
          $ret['error_detail'] = $notFoundList;

          if ($helpMode) {

            $ret['path']                  = $currentEntrypointRoot['_path'];
            $ret['parameters']            = $currentEntrypointRoot['_params'];
            $ret["currentEntrypointRoot"] = json_encode($currentEntrypointRoot);

          } else {
            $ret = false;
          }

          _http_response_code($ret['http_code']);

        } else {
          
          $ret = $this->emptyRet();
          
          _log("\t" . $currentEntrypointRoot['_functionName'] . '()');

          if ($currentEntrypointRoot['_callerClass'] == 'YApiProducerBasis') {

            $ret = call_user_func_array([$this, $currentEntrypointRoot['_functionName']], $params);

          } else {

            $yPluginEntry = $GLOBALS['__yPluginsIndex'][$currentEntrypointRoot['_callerClass']];

            $class = $GLOBALS['__yPluginsRepo'][$yPluginEntry]['_class'];
            $ret   = call_user_func_array([$class, $currentEntrypointRoot['_functionName']], $params);

          }

          // $ret = call_user_func_array($currentEntrypointRoot['_callerClass']->{$currentEntrypointRoot['_functionName']}, $params);
          // $ret = call_user_func_array(
          //   [$currentEntrypointRoot['_callerClass'], $currentEntrypointRoot['_functionName']],
          //   $params);
        
          _log('ret  = ' . json_encode($ret));
          _http_response_code(_getValue($ret, 'http_code', 200));
        }

      } else {
        $ret                 = $this->emptyRet(400, "Entry point '$entrypoint' ($method) not found");
        $ret['error_detail'] = "";
        if ($helpMode) {
          $ret['path']                  = $currentEntrypointRoot['_path'];
          $ret['parameters']            = $currentEntrypointRoot['_params'];
          $ret['currentEntrypointRoot'] = json_encode($currentEntrypointRoot);
        } else {
          $ret = false;
        }
        _log("Entrypoint not found");
        _http_response_code(400);
        _http_response_code($ret['http_code']);
      }
    } else {
      _log("$entrypoint Not found!");
      if ($helpMode) {
        $ret = array("error" => "Entrypoint $entrypoint not found");
      } else {
        $ret = false;
      }

      _http_response_code(400);
    }

    return $ret;

  }

  public function export($applicability = API_DEFAULT)
  {
    if ($applicability == "_undefined_") {
      $applicability = API_DEFAULT;
    }

    _http_response_code(200);

    $swagger = array(
      'info' => array(
        "name"       => $this->apiName,
        "apiVersion" => '%api_VERSION_SEQUENCE%',
        "schema"     => "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"),
      'item' => array(

      ),
    );
    $swaggerList = array();

    foreach ($this->entryPoints as $entryPath => $entryDefinition) {
      // $entry = $entryDefinition;

      foreach ($entryDefinition as $key => $value) {
        //$first_key = key($entry);

        $_callerFile    = $entryDefinition[$key]['_callerFile'];
        $_callerClass   = $entryDefinition[$key]['_callerClass'];
        $_applicability = $entryDefinition[$key]['_applicability'];

        if (($applicability & $_applicability) > 0) {
          $_applicabilityDescription = "";
          foreach ($GLOBALS['__api_applicability_names'] as $appConst => $appDesc) {
            $aux = $appConst & $_applicability;
            if ($aux > 0) {
              if ($_applicabilityDescription > '') {
                $_applicabilityDescription .= ", ";
              }

              $_applicabilityDescription .= $appDesc;
            }
          }
          $_chapterDescription         = null;
          $_chapterDescriptionFilename = false;
          if (empty($swaggerList[$_callerClass])) {
            $swaggerList[$_callerClass] = array();
            $fileNames                  = [
              "$_callerClass.md",
              "$_callerFile.md",
              dirname($_callerFile) . "/$_callerClass.md",
              dirname($_callerFile) . "/$_callerFile.md",
            ];
            for ($i = 0; $i < count($fileNames); $i++) {
              if (file_exists($fileNames[$i])) {
                $_chapterDescriptionFilename = $fileNames[$i];
              }

            }

            if ($_chapterDescriptionFilename !== false) {
              $_chapterDescription = file_get_contents($_chapterDescriptionFilename);
            }
          }

          $method       = str_replace("_", "", mb_strtoupper($key));
          $entryDetails = $entryDefinition[$key];

          $itemEntry = array(
            "name"    => $entryDetails["_functionName"],
            "request" => array(
              "method"        => $method,
              "url"           => array(
                "raw"  => "{{apiURL}}" . $entryDetails["_path"],
                "host" => array("{{apiURL}}"),
                "path" => array($entryDetails["_path"]),
              ),
              "description"   => (!empty($value['_description']) ? $value['_description'] : $_chapterDescription),
              "applicability" => $_applicabilityDescription,
            ),
          );
          $_chapterDescription = null;

          if (("POST" == $method) || ("PUT" == $method)) {
            $itemEntry['request']['body'] = array(
              "mode"     => "formdata",
              "formdata" => array(),
              "options"  => array("formdata" => array()),
            );
            if (isset($entryDetails["_params"])) {
              foreach ($entryDetails["_params"] as $key => $value) {
                $itemEntry['request']['body']['formdata'][] = array(
                  "key"         => $key,
                  "value"       => "",
                  "type"        => $value['type'],
                  "description" => (!empty($value['description']) ? $value['description'] : null),
                );
              }
            } else {
              // die(print_r($entryDetails));
            }
          }

          $swaggerList[$_callerClass][] = $itemEntry;
        }
      }

    }

    foreach ($swaggerList as $name => $item) {
      if (!empty($this->classAliases[$name])) {
        $blockName = $this->classAliases[$name];
      } else {
        $blockName = $name;
      }

      $swagger['item'][] = array(
        "name" => "$blockName",
        "item" => $item,
      );
    }

    $ok   = is_dir("output");
    $erro = "";
    if (!$ok) {
      if (is_writable(getcwd())) {
        $ok = @mkdir("output");
        if (!$ok) {
          $error = "'output' folder could not be created.";
        }
      } else {
        $erro = "Folder " . getcwd() . " cannot be written. Create 'output' folder and give enough rights.";
      }
    }

    if ($ok) {
      $ok = is_writable("output");
      if (!$ok) {
        $erro = "The folder 'output' cannot be written. Change access rights. Using apache, this command can be useful: chcon -R -t httpd_sys_rw_content_t output";
      }
    }

    $outputName = "output/" . $this->apiName . " v%api_VERSION_SEQUENCE% " . date("Y-m-d") . ".json";

    if ($ok) {
      $ok = file_put_contents("$outputName", json_encode($swagger, JSON_PRETTY_PRINT));
      if (!$ok) {
        $error = "The file '$outputName' cannot be written. Delete it or change access rights.";
      }

    }

    $ret = array(
      "ok"     => $ok ? 'true' : 'false',
      "error"  => $erro,
      "output" => $ok ? "$outputName" : "/dev/null");

    return $ret;
  }
}
