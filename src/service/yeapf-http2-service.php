<?php declare(strict_types=1);

namespace YeAPF\Services;

use OpenSwoole\Coroutine\Channel;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\Http\Server;

abstract class HTTP2Service extends ServiceSkeleton
{
    private $error = '';

    private $stackTrace = [];

    private $initialized = false;

    private $APIDetails = [];

    private $usedSecuritySchemes = [];

    private $server = null;

    private $ready = false;

    private $config = null;

    private $handlers = [
        'GET'     => [],
        'POST'    => [],
        'DELETE'  => [],
        'PATCH'   => [],
        'HEAD'    => [],
        'OPTIONS' => [],
        // 'PUT' => [],
        // 'CONNECT' => [],
        // 'TRACE' => [],
    ];

    private $externalURL = null;

    private $clientIP = null;

    private $mainAccess = null;

    public function answerQuery(Bulletin &$bulletin, string $uri)
    {
        $path = explode('?', $uri)[0];
        if (isset($this->routes[$path])) {
            $handler = $this->routes[$path]['handler'];
            return call_user_func($handler, $bulletin);
        }
        return 404;  // Not Found
    }

    public function setHandler(
        string $path,
        callable $attendant,
        array $methods                 = ['GET'],
        callable $attendantConstraints = null
    ) {
        $debug = false;
        // clean path
        $path  = implode('/', $this->getPathFromURI($path));
        if (is_callable($attendant)) {
            foreach ($methods as $method) {
                if (isset($this->handlers[$method])) {
                    // https://www.phpliveregex.com/p/JrD
                    $fnPath = preg_replace('/(\{\{\w+\}\})/', '*', $path);
                    preg_match_all('/\{\{(\w+)\}\}/', $path, $inlineParams);

                    $absentParameter = [];
                    $className       = $attendant[0];
                    $methodName      = $attendant[1];
                    $reflection      = new \ReflectionMethod($className, $methodName);
                    if ($reflection) {
                        $refFunParams = $reflection->getParameters();
                        foreach ($inlineParams[1] as $paramName) {
                            $isParamPresent = false;
                            foreach ($refFunParams as $refParam) {
                                if ($refParam->getName() === $paramName) {
                                    $isParamPresent = true;
                                    break;
                                }
                            }
                            if (!$isParamPresent) {
                                $absentParameter[] = $paramName;
                            }
                        }
                    }
                    if (!empty($absentParameter)) {
                        $errMsg = get_class($className) . '::' . strval($methodName) . '() Missing parameters: ' . implode(', ', $absentParameter);
                        _trace('***********************************');
                        _trace("** $errMsg");
                        _trace('** Actual params: ' . implode(', ', $refFunParams));
                        _trace("** $path DISABLED!");
                        _trace('***********************************');
                        // throw new \YeAPF\YeAPFException($errMsg);
                    } else {
                        $this->handlers[$method][$fnPath] =
                            [
                                'attendant'    => $attendant,
                                'path'         => $path,
                                'inlineParams' => array_combine($inlineParams[0], $inlineParams[1]),
                            ];

                        if (is_callable($attendantConstraints)) {
                            $constraints = $attendantConstraints(\YeAPF_GET_CONSTRAINTS);
                            if ($constraints) {
                                if ($debug)
                                    _trace('constraints****: ' . print_r($constraints, true));
                                $this->handlers[$method][$fnPath]['constraints'] = $constraints->getConstraints();
                            }

                            $this->handlers[$method][$fnPath]['responses']   = $attendantConstraints(\YeAPF_GET_RESPONSES);
                            $this->handlers[$method][$fnPath]['description'] = $attendantConstraints(\YeAPF_GET_DESCRIPTION);
                            $this->handlers[$method][$fnPath]['tags']        = $attendantConstraints(\YeAPF_GET_TAGS);

                            $this->handlers[$method][$fnPath]['operationId'] = $attendantConstraints(\YeAPF_GET_OPERATION_ID);
                            if ($this->handlers[$method][$fnPath]['operationId'] == null) {
                                $this->handlers[$method][$fnPath]['operationId'] = $attendant[1];
                            }

                            $this->handlers[$method][$fnPath]['privatePath'] = $attendantConstraints(\YeAPF_GET_PRIVATE_PATH_FLAG) ?? false;

                            $security = $attendantConstraints(\YeAPF_GET_SECURITY);
                            if (null != $security) {
                                if (is_string($security)) {
                                    $security = [$security];
                                }
                                _trace('SCHEMES in path: ' . implode(',', $security));
                                $knownSchemes = $this->getAPIDetail('components', 'securitySchemes');
                                $schemes      = array_keys($knownSchemes);
                                _trace('SCHEMES in components: ' . implode(',', $schemes));

                                foreach ($security as $requiredSec) {
                                    if (!in_array($requiredSec, $schemes)) {
                                        _trace("Invalid security scheme $requiredSec");
                                        throw new \YeAPF\YeAPFException("Invalid security scheme $requiredSec");
                                    }
                                }

                                $this->usedSecuritySchemes = array_merge($this->usedSecuritySchemes, $security);

                                $this->handlers[$method][$fnPath]['security'] = $security;
                            } else {
                                _trace('No security for ' . $path);
                            }
                        } else {
                            $this->handlers[$method][$fnPath]['privatePath'] = false;
                        }
                    }
                } else {
                    throw new \YeAPF\YeAPFException("Not allowed method $method");
                }
            }
        } else {
            throw new \YeAPF\YeAPFException('Invalid attendant');
        }
    }

    private function findHandler(string $method, string $path)
    {
        $path = implode('/', $this->getPathFromURI($path));
        _trace("Looking for $path");
        $ret = null;
        foreach ($this->handlers[$method] as $pathPattern => $attendant) {
            _trace("Analyzing $path against " . $pathPattern);
            if (fnmatch($pathPattern, $path)) {
                _trace('Found!');
                $ret = $attendant;
                break;
            }
        }
        return $ret;
    }

    public function getPathFromURI(string $uri, ...$expectedPathElements): array
    {
        $path = explode('/', $uri);

        if (empty($path[0])) {
            array_shift($path);
        }

        $sanitizedPathElements = [];
        foreach ($path as $index => $pathElement) {
            $sanitizedPathElement    = preg_replace('/[^a-zA-Z\0-9_\-@:\*\{\}]/', '', $pathElement);
            $sanitizedPathElement    = mb_convert_encoding($sanitizedPathElement ?? '', 'UTF-8', 'UTF-8');
            $sanitizedPathElements[] = $sanitizedPathElement;
        }

        // _log('ARGS: ' . func_num_args());
        // _log('PATH: ' . json_encode($path));
        // _log('URI: ' . $uri);

        $ret = $sanitizedPathElements;
        if (func_num_args() > 1) {
            $ret = array_combine($expectedPathElements, $sanitizedPathElements);
        }

        return $ret;
    }

    public function APIDetailExists($section, $tag = null)
    {
        if (null != $tag) {
            return isset($this->APIDetails[$section][$tag]);
        } else {
            return isset($this->APIDetails[$section]);
        }
    }

    public function setAPIDetail($section, $tag, $value = [])
    {
        if (!$this->APIDetailExists($section)) {
            $this->APIDetails[$section] = [];
        }
        if (is_array($value)) {
            if (!isset($this->APIDetails[$section][$tag])) {
                $this->APIDetails[$section][$tag] = $value;
            } else {
                foreach ($value as $k => $v) {
                    $this->APIDetails[$section][$tag][$k] = $v;
                }
            }
        } else {
            $this->APIDetails[$section][$tag] = $value;
        }
    }

    public function getAPIDetail($section, $tag)
    {
        return $this->APIDetails[$section][$tag] ?? null;
    }

    private function getAsOpenAPIJSON()
    {
        $openApi = [
            'openapi' => '3.0.0',
            'info'    => [
                'title'       => $this->getAPIDetail('info', 'title') ?? 'API Documentation',
                'contact'     => $this->getAPIDetail('info', 'contact') ?? '',
                'description' => $this->getAPIDetail('info', 'description') ?? 'API Documentation',
                'version'     => $this->getAPIDetail('info', 'version') ?? '1.0.0'
            ],
        ];

        if ($this->APIDetailExists('servers')) {
            $openApi['servers'] = [
                [
                    'url'         => $this->getAPIDetail('servers', 'url') ?? $this->getExternalURL(),
                    'description' => $this->getAPIDetail('servers', 'description')
                ]
            ];
        }

        if ($this->APIDetailExists('components', 'securitySchemes')) {
            $openApi['components']['securitySchemes'] = [];
            $declaredSchemes                          = $this->getAPIDetail('components', 'securitySchemes') ?? [];
            foreach ($this->usedSecuritySchemes as $scheme) {
                $openApi['components']['securitySchemes'][$scheme] = $declaredSchemes[$scheme];
            }
        }

        $openApi['paths'] = [];

        $removeCurlyBrakets = function ($str) {
            $str = trim($str);
            $str = str_replace('{{', '{', $str);
            $str = str_replace('}}', '}', $str);
            return $str;
        };

        $openApi['tags'] = [];
        $auxTags         = [];

        foreach ($this->handlers as $method => $endpoints) {
            $method = strtolower($method);
            foreach ($endpoints as $endpoint => $details) {
                if (!$details['privatePath']) {
                    $path = '/' . trim($endpoint);
                    $path = str_replace('//', '/', $path);
                    foreach ($details['inlineParams'] as $placeholder => $paramName) {
                        $pos = strpos($path, '*');
                        if ($pos !== false)
                            $path = substr_replace($path, $removeCurlyBrakets($placeholder), $pos, strlen('1'));
                    }
                    $responses = [];
                    foreach ($details['responses'] ?? [] as $ret_code => $desc) {
                        $responses[$ret_code] = [
                            'description' => $desc
                        ];
                    }
                    if (empty($responses)) {
                        $responses[200] = [
                            'description' => 'Success'
                        ];
                    }
                    $openApi['paths'][$path][$method] = [
                        'summary'   => $details['attendant'][1],
                        'responses' => $responses
                    ];

                    if (!empty($details['tags'])) {
                        $openApi['paths'][$path][$method]['tags'] = $details['tags'];
                        foreach ($details['tags'] as $tag) {
                            if (!in_array($tag, $auxTags)) {
                                $auxTags[] = $tag;
                            }
                        }
                    }

                    if (null != $details['description']) {
                        $openApi['paths'][$path][$method]['description'] = $details['description'];
                    }

                    if (null != $details['operationId'])
                        $openApi['paths'][$path][$method]['operationId'] = $details['operationId'];

                    if (!empty($details['security'])) {
                        foreach ($details['security'] as $secRequired) {
                            $openApi['paths'][$path][$method]['security'][] = [$secRequired => []];
                        }
                    }

                    // Add inline parameters if available
                    if (isset($details['inlineParams']) && is_array($details['inlineParams'])) {
                        foreach ($details['inlineParams'] as $param => $paramName) {
                            if (isset($details['constraints'][$paramName])) {
                                $paramType = $details['constraints'][$paramName]['type'];
                            } else {
                                $paramType = 'string';
                            }
                            $openApi['paths'][$path][$method]['parameters'][] = [
                                'in'       => 'path',
                                'required' => true,
                                'name'     => $paramName,
                                'schema'   => [
                                    'type' => $paramType,
                                ]
                            ];
                        }
                    }

                    // Add request body for POST operation
                    if ($method === 'post' && !empty($details['constraints'])) {
                        $properties = [];
                        $required   = [];
                        _trace('CONSTRAINTS: ' . json_encode($details['constraints']));
                        foreach ($details['constraints'] as $fieldName => $constraint) {
                            if ('datetime' == $constraint['type']) {
                                $auxType = 'string';
                            } else {
                                $auxType = $constraint['type'];
                            }
                            $properties[$fieldName] = [
                                'type' => $auxType,
                            ];
                            if ($constraint['length']) {
                                $properties[$fieldName]['maxLength'] = $constraint['length'];
                            }

                            if ($constraint['required']) {
                                $properties[$fieldName]['minimum'] = 1;
                                $required[]                        = $fieldName;
                            }
                        }

                        $cleanPath = ucfirst(preg_replace('/[^a-zA-Z_0-9]/', '', $path)) . 'RequestBody';

                        $openApi['components']['requestBodies'][$cleanPath] = [
                            'content' => [
                                'application/json'    => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'properties' => $properties
                                    ]
                                ],
                                'multipart/form-data' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'properties' => $properties
                                    ]
                                ]
                            ]
                        ];

                        // application/json
                        // multipart/form-data
                        $openApi['paths'][$path][$method]['requestBody'] = [
                            '$ref' => '#/components/requestBodies/' . $cleanPath
                        ];

                        if (!empty($required)) {
                            $openApi['components']['requestBodies'][$cleanPath]['content']['application/json']['schema']['required']    = $required;
                            $openApi['components']['requestBodies'][$cleanPath]['content']['multipart/form-data']['schema']['required'] = $required;
                        }
                    }
                }
            }
        }

        asort($auxTags);
        foreach ($auxTags as $k => $tag) {
            $openApi['tags'][] = [
                'name' => $tag
            ];
        }
        return $openApi;
    }

    private function viewOpenAPI(\YeAPF\IBulletin &$bulletin)
    {
        $ret = 200;

        $openApi = $this->getAsOpenAPIJSON();

        $bulletin->setJsonString(json_encode($openApi));
        return $ret;
    }

    private function exportOpenAPI(\YeAPF\IBulletin &$bulletin)
    {
        $ret = 200;

        $openApi = $this->getAsOpenAPIJSON();

        $bulletin->setJsonFile(json_encode($openApi));
        $bulletin->setFilename(($this->getAPIDetail('info', 'title') ?? 'API') . '-' . date('YmdHis') . '.json');
        return $ret;
    }

    private function openAPIConstraints(int $verb)
    {
        $ret = null;
        switch ($verb) {
            case YeAPF_GET_PRIVATE_PATH_FLAG:
                $ret = true;
                break;
        }
        return $ret;
    }

    public function configureAndStartup()
    {
        if (!$this->initialized) {
            $this->initialized = true;

            $this->startup();

            $this->setHandler(
                '/openapi/export',
                [$this, 'exportOpenAPI'],
                ['GET'],
                [
                    $this, 'openAPIConstraints'
                ]
            );
            $this->setHandler(
                '/openapi/view',
                [$this, 'viewOpenAPI'],
                ['GET'],
                [
                    $this, 'openAPIConstraints'
                ]
            );
        }
    }

    public function getExternalURL()
    {
        return $this->externalURL;
    }

    public function getClientIP()
    {
        return $this->clientIP;
    }

    public function getMainAccess()
    {
        return $this->mainAccess;
    }

    public function start(?int $port = 9501, ?string $host = '0.0.0.0'): void
    {
        _log("Preparing service on $host:$port");
        if (!$this->ready) {
            // revisar el blog https://blog.restcase.com/4-most-used-rest-api-authentication-methods/
            $this->setAPIDetail(
                'components',
                'securitySchemes',
                [
                    'bearerAuth'   => [
                        'type'   => 'http',
                        'scheme' => 'bearer',
                    ],
                    'basicAuth'    => [
                        'type'   => 'http',
                        'scheme' => 'basic',
                    ],
                    'jwtAuth'      => [
                        'type' => 'apiKey',
                        'name' => 'Authorization',
                        'in'   => 'header',
                    ],
                    'apiKeyHeader' => [
                        'type' => 'apiKey',
                        'in'   => 'header',
                        'name' => 'X-API-Key'
                    ]
                ]
            );

            $server = new Server(
                $host,
                $port,
                \OpenSwoole\Server::POOL_MODE,
                \OpenSwoole\Constant::SOCK_TCP
            );
            $server->set([
                             'open_http2_protocol' => true,
                         ]);

            $server->on('Start', function (Server $server) use ($host, $port) {
                                     _log("Service started on $host:$port\n");
                                     // $this->configureAndStartup();
                                 });

            /**
             * Other events
             *   'WorkerStart': Triggered when a worker process starts.
             *   'WorkerStop': Triggered when a worker process stops.
             *   'WorkerError': Triggered when an error occurs in a worker process.
             *   'Task': Triggered when a task is received by the worker process.
             *   'Finish': Triggered when a task is finished by the worker process.
             *   'Receive': Triggered when a TCP/UDP connection receives data.
             */
            $server->on('Request', function (Request $request, Response $response) use ($server) {
                                       global $currentURI;

                                       \YeAPF\yLogger::setTraceDescriptor('HTTP2 service on ' . $request->server['request_uri']);
                                       // _log("Request arrived");
                                       // \YeAPF\yLogger::log(0, YeAPF_LOG_DEBUG, "REQUEST ARRIVED: " . $request->server['request_uri']);

                                       // $authorizationHeader = $request->getHeaderLine('Authorization');
                                       // $bearerToken = '';

                                       // if (preg_match('/Bearer\s+(.*)/', $authorizationHeader, $matches)) {
                                       //     $bearerToken = $matches[1];
                                       // }

                                       // _trace("Authorization: $authorizationHeader");
                                       // _trace("Bearer token: $bearerToken");

                                       $uri        = urldecode($request->server['request_uri']);
                                       $currentURI = md5($uri);

                                       $params = [];

                                       $startTimestamp = microtime(true);

                                       (function () use ($request) {
                                           $proto             = $request->header['x-forwarded-proto'] ?? '';
                                           $host              = $request->header['host'] ?? '';
                                           $entryURI          = $request->header['x-entry-uri'] ?? '';
                                           $this->externalURL = $proto . '://' . $host . $entryURI;
                                           $this->clientIP    = $request->header['x-forwarded-for'];

                                           $parsed_url = parse_url($this->getExternalURL());
                                           $domain     = $parsed_url['host'];
                                           $port       = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';

                                           $this->mainAccess = $domain . $port;

                                           // $requestTime = date('Y-m-d\TH:i:sP');
                                           // $remoteAddr = $this->getClientIP();
                                           // $status = $ret_code;
                                           $requestMethod   = $request->server['request_method'] ?? 'unknown';
                                           $requestUri      = $request->server['request_uri'] ?? 'unknown';
                                           $requestProtocol = $request->server['server_protocol'] ?? 'unknown';
                                           $httpReferer     = $request->header['referer'] ?? '-';
                                           $httpUserAgent   = $request->header['user-agent'] ?? '-';
                                           $thisHost        = gethostname();
                                           $thisIP          = $request->server['remote_addr'];
                                           $requestTime     = $request->server['request_time'] ?? gmdate('U');
                                           // $thisIP = $request->server['SERVER_ADDR'] ?? 'unknown';

                                           \YeAPF\yLogger::setLogTags(
                                               [
                                                   YeAPF_LOG_TAG_SERVER       => trim($thisHost . ' ' . $thisIP),
                                                   YeAPF_LOG_TAG_SERVICE      => 'yeapf_service',
                                                   YeAPF_LOG_TAG_CLIENT       => $this->getClientIP(),
                                                   YeAPF_LOG_TAG_REQUEST_TIME => gmdate('Y-m-d\TH:i:sP', $requestTime),
                                                   YeAPF_LOG_TAG_REQUEST      => "{$requestMethod} {$requestUri} {$requestProtocol}",
                                                   YeAPF_LOG_TAG_REFERRER     => $httpReferer,
                                                   YeAPF_LOG_TAG_USERAGENT    => $httpUserAgent
                                               ]
                                           );
                                       })();

                                       // _trace('URL: ' . $this->getExternalURL());

                                       // _trace('PATH_INFO: ' . $request->server['path_info']);
                                       // _trace('REQUEST: ' . json_encode($request));

                                       \YeAPF\yLogger::setTraceDetails(
                                           uri: $this->getExternalURL() . $request->server['path_info'],
                                           headers: $request->header,
                                           server: $request->server,
                                           cookie: $request->cookie,
                                           method: $request->server['request_method'],
                                       );

                                       $ret_code     = 406;
                                       $cleanCode    = false;
                                       $serviceStage = 0;

                                       $aBulletin = new \YeAPF\Http2Bulletin();
                                       try {
                                           $method = $request->server['request_method'];
                                           if (mb_strtolower(substr(trim(($request->header['content-type']) ?? ''), 0, 16)) === 'application/json') {
                                               $data = explode("\r\n", $request->getData());

                                               _trace('DATA: ' . json_encode($data));
                                               $jsonData                    = end($data);
                                               $params[strtolower($method)] = json_decode($jsonData, true);
                                               array_pop($data);
                                           } else {
                                           }
                                           $headers = $request->header;

                                           _trace("START $uri ($method)");
                                           $serviceStage = 1;
                                           $this->openContext();
                                           $serviceStage = 2;
                                           $this->configureAndStartup();
                                           $serviceStage = 3;

                                           _trace('ATTENDANTS: ' . json_encode($this->handlers));

                                           $allowedParams = ['cookie', 'get', 'post', 'files'];
                                           foreach ($request as $key => $value) {
                                               if (null != $value && in_array($key, $allowedParams))
                                                   $params[$key] = $value;
                                           }

                                           _trace('PARAMS: ' . json_encode($params));

                                           $handler = $this->findHandler($method, $uri);

                                           _trace('HANDLER: ' . json_encode($handler));
                                           if (null !== $handler) {
                                               $bearerFormat = '';
                                               $secToken     = null;
                                               $minSecOk     = false;

                                               $security = $handler['security'] ?? false;
                                               if (false == $security) {
                                                   _trace("WARNING: No security for $method $uri");
                                                   $minSecOk = true;
                                               } else {
                                                   $knownSchemes = $this->getAPIDetail('components', 'securitySchemes');
                                                   // _trace('KNOWN SCHEMES: ' . print_r($knownSchemes, true));
                                                   _trace('CHOSEN SCHEME: ' . print_r($security, true));
                                                   $secType   = 'notFound';
                                                   $secScheme = 'notDefined';
                                                   if (is_array($security)) {
                                                       foreach ($security as $k => $sec) {
                                                           _trace("KEY: $k VALUE: $sec");
                                                           $secType = $knownSchemes[$sec]['type'];
                                                           if ('http' == $secType) {
                                                               $secScheme = $knownSchemes[$sec]['scheme'];
                                                               if ('bearer' == $secScheme) {
                                                                   $bearerFormat = $knownSchemes[$sec]['bearerFormat'] ?? 'JWT';
                                                               }
                                                           }
                                                       }
                                                   } else {
                                                       if (!empty($knownSchemes[$security])) {
                                                           $secType = $knownSchemes[$security]['type'];
                                                           if ('http' == $secType) {
                                                               $secScheme = $knownSchemes[$security]['scheme'];
                                                               if ('bearer' == $secScheme) {
                                                                   $bearerFormat = $knownSchemes[$sec]['bearerFormat'] ?? 'JWT';
                                                               }
                                                           }
                                                       }
                                                   }
                                                   _trace("SECURITY TYPE: $secType");
                                                   _trace("SECURITY SCHEME: $secScheme");

                                                   if ('notFound' == $secType) {
                                                       $minSecOk = false;
                                                       _trace("ERROR: Security declared for $method $uri but not found");
                                                   } else {
                                                       preg_match('/' . $secScheme . ' (.*)/i', $headers['authorization'] ?? '', $output_array);
                                                       if ($output_array) {
                                                           $secToken = $output_array[1];
                                                           _trace("SECURITY TOKEN: $secToken");
                                                           $minSecOk = true;
                                                       }
                                                   }
                                               }

                                               $serviceStage = 4;

                                               $inlineVariables = new \YeAPF\SanitizedKeyData($handler['constraints'] ?? null);
                                               $pathSegments    = explode('/', $handler['path']);
                                               $uriSegments     = $this->getPathFromURI($uri);

                                               foreach ($handler['inlineParams'] as $inlineParam => $inlineName) {
                                                   $paramIndex = array_search($inlineParam, $pathSegments);
                                                   $uriSegment = $uriSegments[$paramIndex];
                                                   if (is_numeric($uriSegment)) {
                                                       if (strpos($uriSegment, '.') !== false) {
                                                           $inlineVariables[$inlineName] = (float) $uriSegment;
                                                       } else {
                                                           $inlineVariables[$inlineName] = (int) $uriSegment;
                                                       }
                                                   } else {
                                                       $inlineVariables[$inlineName] = $uriSegment;
                                                   }
                                               }
                                               _trace('INLINES: ' . json_encode($inlineVariables));

                                               $serviceStage = 5;

                                               $aux = new \YeAPF\SanitizedKeyData($handler['constraints'] ?? null);
                                               try {
                                                   if ($minSecOk) {
                                                       $tokenNotUsable = false;
                                                       if ('JWT' == $bearerFormat) {
                                                           $params['__secToken'] = [];
                                                           $aJWT                 = new \YeAPF\Security\yJWT($secToken);
                                                           $params['__secToken'] = [
                                                               'text' => $secToken
                                                           ];
                                                           $importResult         = $aJWT->getImportResult();
                                                           if ($importResult == YeAPF_JWT_SIGNATURE_VERIFICATION_OK) {
                                                               $params['__secToken']['jwt'] = $aJWT->exportData();
                                                               $expirationTime              = $aJWT->exp;

                                                               $tokenNotUsable = ($expirationTime < time());
                                                               _trace("Expiration time: $expirationTime");
                                                               _trace('   Current Time: ' . time());
                                                               _trace('      Time diff: ' . ($expirationTime - time()));
                                                               _trace('Token expiration: ' . ($tokenNotUsable ? 'Achieved' : 'Not achieved'));
                                                               _trace('Decoded token:' . print_r($aJWT->exportData(), true));

                                                               if ($tokenNotUsable) {
                                                                   _trace('Token expired');
                                                                   $aBulletin->reason = 'Token expired';
                                                                   $ret_code          = 401;
                                                               } else {
                                                                   if ($aJWT->tokenInBin()) {
                                                                       $tokenNotUsable    = true;
                                                                       $aBulletin->reason = 'Token already deleted';
                                                                       $ret_code          = 401;
                                                                   } else {
                                                                       if (($aJWT->nbf ?? 0) > time()) {
                                                                           $tokenNotUsable    = true;
                                                                           $aBulletin->reason = 'Token not yet valid. Use only after ' . date('Y-m-d H:i:s', $aJWT->nbf ?? 0) . ' Issued at ' . date('Y-m-d H:i:s', $aJWT->iat) . ' Current time: ' . date('Y-m-d H:i:s', time());
                                                                           $ret_code          = 401;
                                                                       } else {
                                                                           if ($aJWT->iss != $this->getMainAccess()) {
                                                                               $tokenNotUsable    = true;
                                                                               $aBulletin->reason = "Token issued for another server '" . $aJWT->iss . "' and not for '" . $this->getMainAccess() . "'";
                                                                               $ret_code          = 401;
                                                                           }
                                                                       }
                                                                   }
                                                               }
                                                           } else {
                                                               $tokenNotUsable    = true;
                                                               $aBulletin->reason = 'Token cannot be used. 0x' . dechex($importResult) . ':' . $aJWT->explainImportResult();
                                                               $ret_code          = 401;

                                                               _trace($aBulletin->reason);
                                                           }
                                                       }
                                                       if ($tokenNotUsable) {
                                                           if (406 == $ret_code) {
                                                               $aBulletin->reason = $aBulletin->reason ?? 'Token expired or damaged';
                                                               $ret_code          = 401;
                                                           }
                                                       } else {
                                                           _trace("Calling handler >>>> $method $uri");
                                                           // check this
                                                           // aux appears to 1) import as referential array and 2) not being used
                                                           $aux->importData($params);

                                                           $serviceStage = 6;

                                                           $ret_code  = $handler['attendant']($aBulletin, $uri, $params, ...$inlineVariables);
                                                           $cleanCode = true;

                                                           $serviceStage = 8;

                                                           if ($ret_code >= 200 && $ret_code <= 299) {
                                                               if ('JWT' == $bearerFormat) {
                                                                   if ($aJWT->uot) {
                                                                       _trace("Discarding token $secToken");
                                                                       $aJWT->sendToBin($secToken);
                                                                   }
                                                               }
                                                           }
                                                       }
                                                   } else {
                                                       _trace('Security not satisfied');
                                                       $aBulletin->reason = 'Method not allowed';
                                                       $ret_code          = 405;
                                                   }
                                               } catch (\Exception $e) {
                                                   $this->error = $e->getMessage();
                                                   _trace($e->getMessage());
                                                   $aBulletin->reason = $e->getMessage();
                                                   $ret_code          = 500;
                                               }
                                           } else {
                                               $ret_code = $this->answerQuery($aBulletin, $uri, $params) ?? 204;
                                           }
                                       } catch (\Exception $e) {
                                           _trace('EXCEPTION: ' . $e->getMessage());
                                           $this->stackTrace = [
                                               'code'    => $e->getCode(),
                                               'file'    => $e->getFile(),
                                               'line'    => $e->getLine(),
                                               'message' => $e->getMessage(),
                                               'trace'   => $e->getTrace()
                                           ];
                                           $ret_code         = 550;
                                       } catch (\Error $e) {
                                           _trace('ERROR: ' . $e->getMessage());
                                           $this->stackTrace  = [
                                               'code'    => $e->getCode(),
                                               'file'    => $e->getFile(),
                                               'line'    => $e->getLine(),
                                               'message' => $e->getMessage(),
                                               'trace'   => $e->getTrace()
                                           ];
                                           $this->error       = $e->getMessage();
                                           $aBulletin->reason = $e->getMessage();
                                           $ret_code          = 551;
                                       } catch (\Throwable $e) {
                                           _trace('THROWABLE: ' . $e->getMessage());
                                           $this->stackTrace  = [
                                               'code'    => $e->getCode(),
                                               'file'    => $e->getFile(),
                                               'line'    => $e->getLine(),
                                               'message' => $e->getMessage(),
                                               'trace'   => $e->getTrace()
                                           ];
                                           $this->error       = $e->getMessage();
                                           $aBulletin->reason = $e->getMessage();
                                           $ret_code          = 552;
                                       } finally {
                                           if (!$cleanCode) {
                                               if ($ret_code < 500) {
                                                   $ret_code = 500;
                                               }
                                               _trace("NOT CLEAN CODE: $ret_code");
                                           }

                                           $contentLength = strlen(json_encode($aBulletin->exportData()));
                                           \YeAPF\yLogger::setLogTags(
                                               [
                                                   YeAPF_LOG_TAG_RESULT        => $ret_code,
                                                   YeAPF_LOG_TAG_RESPONSE_SIZE => $contentLength,
                                                   YeAPF_LOG_TAG_RESPONSE_TIME => microtime(true) - $startTimestamp
                                               ]
                                           );

                                           if ($ret_code > 299) {
                                               \YeAPF\yLogger::log(0, YeAPF_LOG_ERROR, \YeAPF\yLogger::getTraceFilename() . ' ' . $this->error);
                                               if (!empty($this->stackTrace)) {
                                                   \YeAPF\handleException(...$this->stackTrace);
                                               }
                                           } else {
                                               \YeAPF\yLogger::log(0, YeAPF_LOG_DEBUG);
                                           }

                                           // _log("RETURN: $ret_code BODY: " . json_encode($aBulletin->exportData()));

                                           \YeAPF\yLogger::setTraceDetails(
                                               httpCode: $ret_code,
                                               return: $aBulletin->exportData()
                                           );
                                           // _trace("RETURN: $ret_code BODY: " . json_encode($aBulletin->exportData()));
                                           $aBulletin($ret_code, $request, $response);

                                           // _trace("FINNISH $uri");
                                           $this->closeContext();
                                           \YeAPF\yLogger::closeTrace($ret_code > 299);
                                       }
                                   });

            $server->on('Close', function (Server $server) use ($host, $port) {
                                     $this->closeContext();
                                     // \YeAPF\yLogger::log(0, YeAPF_LOG_INFO, "Service closed at $host:$port\n");
                                 });

            $server->start();
        }
    }
}
