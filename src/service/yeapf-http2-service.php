<?php declare(strict_types=1);

namespace YeAPF\Services;

use OpenSwoole\Coroutine\Channel;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\Http\Server;

abstract class HTTP2Service
{
    private $initialized = false;

    private $APIDetails = [];

    private $usedSecuritySchemes = [];

    private $server = null;

    private $ready = false;

    private $config = null;

    private $context = null;

    private $handlers = [
        'GET' => [],
        'POST' => [],
        'DELETE' => [],
        'PATCH' => [],
        'HEAD' => [],
        'OPTIONS' => [],
        // 'PUT' => [],
        // 'CONNECT' => [],
        // 'TRACE' => [],
    ];

    private $externalURL = null;

    abstract function startup();
    abstract function shutdown();
    abstract function answerQuery(\YeAPF\Bulletin &$bulletin, string $uri);

    public function setHandler(
        string $path,
        callable $attendant,
        array $methods = ['GET'],
        callable $attendantConstraints = null
    ) {
        $debug = false;
        // clean path
        $path = implode('/', $this->getPathFromURI($path));
        if (is_callable($attendant)) {
            foreach ($methods as $method) {
                if (isset($this->handlers[$method])) {
                    // https://www.phpliveregex.com/p/JrD
                    $fnPath = preg_replace('/(\{\{\w+\}\})/', '*', $path);
                    preg_match_all('/\{\{(\w+)\}\}/', $path, $inlineParams);

                    $this->handlers[$method][$fnPath] =
                        [
                            'attendant' => $attendant,
                            'path' => $path,
                            'inlineParams' => array_combine($inlineParams[0], $inlineParams[1]),
                        ];

                    if (is_callable($attendantConstraints)) {
                        $constraints = $attendantConstraints(\YeAPF_GET_CONSTRAINTS);
                        if ($constraints) {
                            if($debug) _log('constraints****: ' . print_r($constraints, true));
                            $this->handlers[$method][$fnPath]['constraints'] = $constraints->getConstraints();
                        }

                        $this->handlers[$method][$fnPath]['responses'] = $attendantConstraints(\YeAPF_GET_RESPONSES);
                        $this->handlers[$method][$fnPath]['description'] = $attendantConstraints(\YeAPF_GET_DESCRIPTION);

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
                            _log('SCHEMES in path: ' . implode(',', $security));
                            $knownSchemes = $this->getAPIDetail('components', 'securitySchemes');
                            $schemes = array_keys($knownSchemes);
                            _log('SCHEMES in components: ' . implode(',', $schemes));

                            foreach ($security as $requiredSec) {
                                if (!in_array($requiredSec, $schemes)) {
                                    _log("Invalid security scheme $requiredSec");
                                    throw new \Exception("Invalid security scheme $requiredSec");
                                }
                            }

                            $this->usedSecuritySchemes = array_merge($this->usedSecuritySchemes, $security);

                            $this->handlers[$method][$fnPath]['security'] = $security;
                        } else {
                            _log('No security for '.$path);
                        }
                    } else {
                        $this->handlers[$method][$fnPath]['privatePath'] = false;
                    }
                } else {
                    throw new \Exception("Not allowed method $method");
                }
            }
        } else {
            throw new \Exception('Invalid attendant');
        }
    }

    private function findHandler(string $method, string $path)
    {
        $path = implode('/', $this->getPathFromURI($path));
        _log("Looking for $path");
        $ret = null;
        foreach ($this->handlers[$method] as $pathPattern => $attendant) {
            _log("Analyzing $path against " . $pathPattern);
            if (fnmatch($pathPattern, $path)) {
                _log('Found!');
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
            $sanitizedPathElement = preg_replace('/[^a-zA-Z0-9_\-@:\*\{\}]/', '', $pathElement);
            $sanitizedPathElement = mb_convert_encoding($sanitizedPathElement ?? '', 'UTF-8', 'UTF-8');
            $sanitizedPathElements[] = $sanitizedPathElement;
        }

        _log('ARGS: ' . func_num_args());
        _log('PATH: ' . json_encode($path));
        _log('URI: ' . $uri);

        $ret = $sanitizedPathElements;
        if (func_num_args() > 1) {
            $ret = array_combine($expectedPathElements, $sanitizedPathElements);
        }

        return $ret;
    }

    public function APIDetailExists($section, $tag = null)
    {
        if (null != $tag) {
            return isset($this->APIDetail[$section][$tag]);
        } else {
            return isset($this->APIDetail[$section]);
        }
    }

    public function setAPIDetail($section, $tag, $value = [])
    {
        if (!$this->APIDetailExists($section)) {
            $this->APIDetail[$section] = [];
        }
        if (is_array($value)) {
            if (!isset($this->APIDetail[$section][$tag])) {
                $this->APIDetail[$section][$tag] = $value;
            } else {
                foreach ($value as $k => $v) {
                    $this->APIDetail[$section][$tag][$k] = $v;
                }
            }
        } else {
            $this->APIDetail[$section][$tag] = $value;
        }
    }

    public function getAPIDetail($section, $tag)
    {
        return $this->APIDetail[$section][$tag] ?? null;
    }

    private function getAsOpenAPIJSON()
    {
        $openApi = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => $this->getAPIDetail('info', 'title') ?? 'API Documentation',
                'version' => $this->getAPIDetail('info', 'version') ?? '1.0.0'
            ],
        ];

        if ($this->APIDetailExists('servers')) {
            $openApi['servers'] = [
                [
                    'url' => $this->getAPIDetail('servers', 'url') ?? $this->externalURL,
                    'description' => $this->getAPIDetail('servers', 'description')
                ]
            ];
        }

        if ($this->APIDetailExists('components', 'securitySchemes')) {
            $openApi['components']['securitySchemes'] = [];
            $declaredSchemes = $this->getAPIDetail('components', 'securitySchemes') ?? [];
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
                        'summary' => $details['attendant'][1],
                        'responses' => $responses
                    ];

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
                                'in' => 'path',
                                'required' => true,
                                'name' => $paramName,
                                'schema' => [
                                    'type' => $paramType,
                                ]
                            ];
                        }
                    }

                    // Add request body for POST operation
                    if ($method === 'post' && !empty($details['constraints'])) {
                        $properties = [];
                        $required = [];
                        _log('CONSTRAINTS: ' . json_encode($details['constraints']));
                        foreach ($details['constraints'] as $fieldName => $constraint) {
                            $properties[$fieldName] = [
                                'type' => $constraint['type'],
                            ];
                            if ($constraint['length']) {
                                $properties[$fieldName]['maxLength'] = $constraint['length'];
                            }

                            if ($constraint['required']) {
                                $properties[$fieldName]['minimum'] = 1;
                                $required[] = $fieldName;
                            }
                        }

                        $cleanPath = ucfirst(preg_replace('/[^a-zA-Z_0-9]/', '', $path)) . 'RequestBody';

                        $openApi['components']['requestBodies'][$cleanPath] = [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => $properties
                                    ]
                                ],
                                'multipart/form-data' => [
                                    'schema' => [
                                        'type' => 'object',
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
                            $openApi['components']['requestBodies'][$cleanPath]['content']['application/json']['schema']['required'] = $required;
                            $openApi['components']['requestBodies'][$cleanPath]['content']['multipart/form-data']['schema']['required'] = $required;
                        }
                    }
                }
            }
        }
        return $openApi;
    }

    private function viewOpenAPI(\YeAPF\Bulletin &$bulletin)
    {
        $ret = 200;

        $openApi = $this->getAsOpenAPIJSON();

        $bulletin->__json = json_encode($openApi);
        return $ret;
    }

    private function exportOpenAPI(\YeAPF\Bulletin &$bulletin)
    {
        $ret = 200;

        $openApi = $this->getAsOpenAPIJSON();

        $bulletin->__jsonFile = json_encode($openApi);
        $bulletin->__filename = ($this->getAPIDetail('info', 'title') ?? 'API') . '-' . date('YmdHis') . '.json';
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

    private function configureAndStartup()
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

    private function openContext()
    {
        $this->context = new \YeAPF\Connection\PersistenceContext(
            new \YeAPF\Connection\DB\RedisConnection(),
            new \YeAPF\Connection\DB\PDOConnection()
        );
    }

    public function getContext()
    {
        return $this->context;
    }

    private function closeContext()
    {
        $this->shutdown();
        $this->context = null;
    }

    public function getExternalURL()
    {
        return $this->externalURL;
    }

    public function start($port = 9501, $host = '0.0.0.0')
    {
        _log("Starting service on $host:$port");
        if (!$this->ready) {
            $this->setAPIDetail(
                'components',
                'securitySchemes',
                [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                    ],
                    'basicAuth' => [
                        'type' => 'http',
                        'scheme' => 'basic',
                    ],
                    'jwtAuth' => [
                        'type' => 'apiKey',
                        'name' => 'Authorization',
                        'in' => 'header',
                    ],
                    'apiKeyHeader' => [
                        'type' => 'apiKey',
                        'in' => 'header',
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
                _log("Service started at $host:$port\n");
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
            $server->on('Request', function (Request $request, Response $response) {
                global $currentURI;

                // $authorizationHeader = $request->getHeaderLine('Authorization');
                // $bearerToken = '';

                // if (preg_match('/Bearer\s+(.*)/', $authorizationHeader, $matches)) {
                //     $bearerToken = $matches[1];
                // }

                // _log("Authorization: $authorizationHeader");
                // _log("Bearer token: $bearerToken");

                $uri = urldecode($request->server['request_uri']);
                $currentURI = md5($uri);

                $params = [];

                (function () use ($request) {
                    $proto = $request->header['x-forwarded-proto'];
                    $host = $request->header['host'];
                    $entryURI = $request->header['x-entry-uri'];
                    $this->externalURL = $proto . '://' . $host . $entryURI;
                })();

                _log('URL: ' . $this->externalURL);

                _log('PATH_INFO: ' . $request->server['path_info']);
                _log('REQUEST: ' . json_encode($request));
                $ret_code = 406;

                try {
                    $method = $request->server['request_method'];
                    if ($request->header['content-type'] === 'application/json') {
                        $data = explode("\r\n", $request->getData());

                        _log('DATA: ' . json_encode($data));
                        $jsonData = end($data);
                        $params[strtolower($method)] = json_decode($jsonData, true);
                        array_pop($data);

                    } else {
                    }
                    $headers = $request->header;

                    _log("START $uri ($method)");
                    $this->openContext();
                    $this->configureAndStartup();

                    $aBulletin = new \YeAPF\Bulletin();

                    _log('ATTENDANTS: ' . json_encode($this->handlers));

                    $allowedParams = ['cookie', 'get', 'post', 'files'];
                    foreach ($request as $key => $value) {
                        if (null != $value && in_array($key, $allowedParams))
                            $params[$key] = $value;
                    }

                    _log('PARAMS: ' . json_encode($params));

                    $handler = $this->findHandler($method, $uri);

                    _log('HANDLER: ' . json_encode($handler));
                    if (null !== $handler) {
                        $bearerFormat = '';
                        $secToken = null;

                        $security = $handler['security'] ?? false;
                        if (false == $security) {
                            _log("WARNING: No security for $method $uri");
                            $minSecOk = true;
                        } else {
                            $knownSchemes = $this->getAPIDetail('components', 'securitySchemes');
                            _log('KNOWN SCHEMES: ' . print_r($knownSchemes, true));
                            _log('CHOSEN SCHEME: ' . print_r($security, true));
                            $secType = 'notFound';
                            $secScheme = 'notDefined';
                            if (is_array($security)) {
                                foreach ($security as $k => $sec) {
                                    _log("KEY: $k VALUE: $sec");
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
                            _log("SECURITY TYPE: $secType");
                            _log("SECURITY SCHEME: $secScheme");

                            if ('notFound' == $secType) {
                                $minSecOk = false;
                                _log("ERROR: Security declared for $method $uri but not found");
                            } else {
                                preg_match('/' . $secScheme . ' (.*)/i', $headers['authorization'] ?? '', $output_array);
                                if ($output_array) {
                                    $secToken = $output_array[1];
                                    _log("SECURITY TOKEN: $secToken");
                                    $minSecOk = true;
                                }
                            }
                        }

                        $inlineVariables = new \YeAPF\SanitizedKeyData($handler['constraints']??null);
                        $pathSegments = explode('/', $handler['path']);
                        $uriSegments = $this->getPathFromURI($uri);

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
                        _log('INLINES: ' . json_encode($inlineVariables));

                        $aux = new \YeAPF\SanitizedKeyData($handler['constraints']??null);
                        try {
                            if ($minSecOk) {
                                $tokenExpirationAchieved = false;
                                if ('JWT' == $bearerFormat) {
                                    $aJWT = new \YeAPF\Security\yJWT($secToken);
                                    if ($aJWT->getImportResult()) {
                                        $expirationTime = $aJWT->exp;

                                        $tokenExpirationAchieved = ($expirationTime < time());
                                        _log("Expiration time: $expirationTime");
                                        _log("   Current Time: " . time());
                                        _log("      Time diff: ".($expirationTime - time()));
                                        _log("Token expiration: ". ($tokenExpirationAchieved ? 'Achieved' : 'Not achieved'));
                                        _log("Decoded token:".print_r($aJWT->exportData(), true));
                                    } else {
                                        $tokenExpirationAchieved = true;
                                        $aBulletin->message = "Token cannot be used: " . $aJWT->explainImportResult();
                                        _log("Token cannot be used. Import result: ".$aJWT->explainImportResult());
                                    }
                                }
                                if ($tokenExpirationAchieved) {
                                    _log("TOKEN EXPIRED");
                                    $aBulletin->message = 'Token expired';
                                    $ret_code = 401;
                                } else {
                                    _log("Calling handler >>>> $method $uri");
                                    $aux->importData($params);
                                    $ret_code = $handler['attendant']($aBulletin, $uri, $params, ...$inlineVariables) ?? 500;

                                    if ($ret_code >= 200 && $ret_code <= 299) {
                                        if ('JWT' == $bearerFormat) {
                                            if ($aJWT->uot) {
                                                _log("Discarding token $secToken");
                                                $aJWT->sendToBin($secToken);
                                            }
                                        }
                                    }
                                }
                            } else {
                                _log('Security not satisfied');
                                $aBulletin->message = 'Method not allowed';
                                $ret_code = 405;
                            }
                        } catch (\Exception $e) {
                            _log($e->getMessage());
                            $ret_code = 500;
                        }
                    } else {
                        $ret_code = $this->answerQuery($aBulletin, $uri, $params) ?? 204;
                    }
                } finally {
                    _log("RETURN: $ret_code BODY: " . json_encode($aBulletin->exportData()));
                    $aBulletin($ret_code, $request, $response);

                    _log("FINNISH $uri");
                    $this->closeContext();
                }
            });

            $server->on('Close', function (Server $server) use ($host, $port) {
                $this->closeContext();
                _log("Service closed at $host:$port\n");
            });

            $server->start();
        }
    }
}
