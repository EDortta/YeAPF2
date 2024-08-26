<?php declare(strict_types=1);

namespace YeAPF;

class WebApp
{
    static $uri = null;
    static $folder = null;
    static $mode = null;
    static $uselessURI = 0;

    static private $handlers = [
        'GET' => [],
        'POST' => [],
        'DELETE' => [],
        'PATCH' => [],
        'HEAD' => [],
        'OPTIONS' => [],
    ];

    public static function initialize()
    {
        if (null == self::$mode) {
            self::$mode = \YeAPF\YeAPFConfig::getSection('mode')->mode ?? 'devel';
        }
    }

    /**
     * Sets the level of useless URI.
     *
     * @param int $level The level of useless URI.
     */
    public static function setUselessURILevel($level)
    {
        self::$uselessURI = $level;
    }

    static function getCurrentURL()
    {
        $url = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $url .= 's';
        } else {
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
                $url .= 's';
            }
        }
        $url .= '://' . $_SERVER['HTTP_HOST'];
        $url .= $_SERVER['REQUEST_URI'];
        return $url;
    }

    static function getBaseURL()
    {
        $url = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $url .= 's';
        } else {
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
                $url .= 's';
            }
        }
        $url .= '://' . $_SERVER['HTTP_HOST'];
        $url .= dirname($_SERVER['DOCUMENT_URI']??'');
        return $url;
    }

    static function getMainAccess()
    {
        $ret = dirname(self::getBaseURL());
        $ret = preg_replace('#^(https?://)#', '', $ret);
        return $ret;
    }

    static function getURI($defaultURI = '')
    {
        if (null == self::$uri) {
            self::$folder = dirname($_SERVER['PHP_SELF']);
            $uri = $_SERVER['REQUEST_URI']??'';

            $uri = substr($uri, strlen(self::$folder));

            if (substr($uri, 0, 1) == '/') {
                $uri = substr($uri, 1);
            }

            if (strlen($uri) == 0) {
                $uri = $defaultURI;
            }

            self::$uri = $uri;
        }
        return self::$uri;
    }

    static function setURI($uri)
    {
        self::$uri = $uri;
        return self::$uri;
    }

    static function splitURI()
    {
        $uri = self::getURI();
        $uri = explode('/', $uri);
        $ret = [];

        foreach ($uri as $u) {
            if (strlen($u) > 0) {
                $ret[] = explode('?', $u)[0];
            }
        }
        return $ret;
    }

    static function clientExpectJSON()
    {
        // echo "<pre>";
        // die(print_r($_SERVER));
        $ret = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        // die("ret = $ret");
        return $ret;
    }

    static function getRequest()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input)) {
            $ret = array_merge($_REQUEST, $input);
        } else {
            $ret = $_REQUEST;
        }
        return $ret;
    }

    static function generateRandomVariableName($length = 8): string
    {
        $bytes = random_bytes($length);
        $name = bin2hex($bytes);
        $name = preg_replace('/^[0-9]+/', '', $name);
        $name = substr($name, 0, $length);
        $name = 'ts_' . $name;
        return $name;
    }

    static function applyAntiCache($content, $antiCacheParticle)
    {
        $randId = '';
        $randNumber = 0;
        $antiCacheURI = '';
        if ($antiCacheParticle === true) {
            $randNumber = mt_rand(1000, 9999);
            $randId = self::generateRandomVariableName();
            $antiCacheURI = $randId . 'Z=' . $randNumber;
        } elseif ($antiCacheParticle !== false) {
            $antiCacheURI = $antiCacheParticle;
        } else {
            $antiCacheURI = '';
        }

        $filesHookExpr = '/(\<(script|link)[\n\t ]*[a-zA-Z0-9_\-"\'+\= ]+[\n\t ]*(src|href)=["\']{1,})([^"\']*)(["\']{1,}[^>]*>)/i';
        if (self::$mode != 'devel') {
            $content = preg_replace_callback($filesHookExpr, function ($matches) use ($randId, $randNumber, $antiCacheURI) {
                $file = $matches[4];
                if (strpos($file, '.js') > 0)
                    $minFile = preg_replace('/\.js$/', '.min.js', $file);
                else
                    $minFile = preg_replace('/\.css$/', '.min.css', $file);

                _trace("minFile: $minFile");

                $minFile = explode('/', $minFile);
                $folder = '';
                do {
                    $particle = array_shift($minFile);
                    $folder .= $particle . '/';
                } while ($particle == '');
                $minFile = implode('/', $minFile);

                // Check if the .min file exists
                if (file_exists($minFile)) {
                    return $matches[1] . $folder . $minFile . '?' . $antiCacheURI . $matches[5];
                } else {
                    // return $matches[0];
                    return $matches[1] . $matches[4] . '?' . $antiCacheURI . $matches[5];
                }
            }, $content);
        } else {
            if (is_array($content) || is_string($content))
                $content = preg_replace($filesHookExpr, '$1$4?' . $antiCacheURI . '$5', $content ?? '');
        }

        return $content;
    }

    static function setRouteHandler($path, $method, $handler)
    {
        if (!isset(self::$handlers[$method])) {
            throw new \YeAPF\YeAPFException("Not allowed method $method");
        } else {
            // $path = ltrim($path, '/');
            // $path = ltrim($path, '\/');

            $pSlash = strrpos($path, '/');
            $pEscapedSlash = strrpos($path, '\/');
            if ($pSlash != $pEscapedSlash + 1) {
                $path = preg_quote($path, '/');
            }

            $path = str_replace('\{\{', '{{', $path);
            $path = str_replace('\}\}', '}}', $path);
            $path = str_replace('\:\:', '::', $path);

            $path = str_replace('*', '([^\/\s]*)', $path);

            $typedParameterExpression = '/\{\{([\w]+)::([\w]+)\}\}/';
            if (preg_match_all($typedParameterExpression, $path, $matches)) {
                $tmpHandlerParams = [];
                $p0 = 0;
                $regexpPath = '';
                foreach ($matches[1] as $index => $paramName) {
                    $paramType = $matches[2][$index];

                    $paramDeclaration = '{{' . $paramName . '::' . $paramType . '}}';
                    $p1 = strpos($path, $paramDeclaration, $p0);
                    $p2 = strpos($path, $paramDeclaration, $p1 + strlen($paramDeclaration));
                    if ($p2 !== false) {
                        throw new \YeAPF\YeAPFException("Parameter '$paramName' already declared in path $path");
                    }

                    $typeDefinition = BasicTypes::get($paramType);
                    if (null == $typeDefinition) {
                        throw new \YeAPF\YeAPFException("Type '$paramType' not found when declaring '$path'");
                    }

                    $auxRegExpression = $typeDefinition['regExpression'];
                    $auxRegExpression = str_replace('/^', '', $auxRegExpression);
                    $auxRegExpression = str_replace('$/', '', $auxRegExpression);
                    $auxRegExpression = rtrim($auxRegExpression, '/');
                    if (substr($auxRegExpression, 0, 1) != '(' || substr($auxRegExpression, -1, 1) != ')') {
                        $auxRegExpression = '(' . $auxRegExpression . ')';
                    }

                    $regexpPath .= substr($path, $p0, $p1 - $p0) . $auxRegExpression;
                    $p0 = $p1 + strlen($paramDeclaration);

                    $tmpHandlerParams[] = [
                        'name' => $paramName,
                        'type' => $paramType
                    ];
                }

                $fnPath = preg_replace($typedParameterExpression, '*', $path);
                $fnPath = str_replace('\/', '/', $fnPath);
                if (isset(self::$handlers[$method][$regexpPath])) {
                    throw new \YeAPF\YeAPFException("Path $path already exists");
                } else {
                    self::$handlers[$method][$regexpPath] = [
                        'handler' => $handler,
                        'fnAlias' => $fnPath,
                        'parameters' => []
                    ];
                    self::$handlers[$method][$regexpPath]['parameters'] = $tmpHandlerParams;
                }
            } else {
                if (isset(self::$handlers[$method][$path])) {
                    throw new \YeAPF\YeAPFException("Path $path already exists");
                } else {
                    $fnPath = preg_replace('/\((.*)\)/', '*', $path);
                    $fnPath = str_replace('\/', '/', $fnPath);
                    self::$handlers[$method][$path] = [
                        'handler' => $handler,
                        'fnAlias' => $fnPath,
                        'parameters' => []
                    ];
                }
            }
        }
    }

    static function getRouteHandlerDefinition($path, $method)
    {
        $ret = null;

        if (substr($path, 0, 1) != '/') {
            $path = '/' . $path;
        }

        $matches = [];
        foreach (self::$handlers[$method] as $pattern => $pathDefinition) {
            if (preg_match('/' . $pattern . '/', $path, $match)) {
                $matches[$pattern] = $pathDefinition;
            }
        }

        krsort($matches);
        reset($matches);
        $ret = current($matches);

        if (count(explode('/', $path)) != count(explode('/', key($matches) ?? ''))) {
            $ret = null;
        }
        return $ret;
    }

    static function renderPage($uri, &$context, string|null|bool $antiCache = true)
    {
        header('Content-Type: text/html; charset=utf-8');

        global $yAnalyzer;

        $entrance = '';
        $uri = explode('/', $uri);
        $c = self::$uselessURI;
        while ($c > 0) {
            $entrance = array_shift($uri) . '/';
            $c--;
        }
        $entrance = rtrim($entrance, '/');

        $uri = implode('/', $uri);
        if (substr($uri, -4) == '.htm' || substr($uri, -5) == '.html') {
            $uri = substr($uri, 0, strlen($uri) - 5);
        }

        $content = '';

        if (!file_exists("template/pages/$entrance/$entrance.html")) {
            $entrance = '404';
        }

        $context['mode'] = self::$mode;

        if (file_exists("template/pages/$entrance/$entrance.html")) {
            $content = file_get_contents("template/pages/$entrance/$entrance.html");
            $content = str_replace('../../', self::$folder . '/template/', $content);

            $page_content = "Content of 'pages/$uri.html' or 'pages/$uri/$uri.html' not found!";

            if (file_exists("pages/$uri.html")) {
                $page_content = file_get_contents("pages/$uri.html");
                if ($antiCache) {
                    // $page_content = self::applyAntiCache($page_content, $antiCache);
                }
            } else {
                if (file_exists("pages/$uri/$uri.html")) {
                    $page_content = file_get_contents("pages/$uri/$uri.html");
                    if ($antiCache) {
                        // $page_content = self::applyAntiCache($page_content, $antiCache);
                    }
                }
            }
            $context['page_content'] = $yAnalyzer->do($page_content, $context);
        } else {
            $places = ["pages/$uri.html", "pages/$uri/$uri.html", "$uri.html", "$uri/$uri.html"];
            $pageFound = false;
            foreach ($places as $place) {
                if (file_exists($place)) {
                    $content = file_get_contents($place);
                    if ($antiCache) {
                        // $content = self::applyAntiCache($content, $antiCache);
                    }
                    $pageFound = true;
                    break;
                }
            }
            if (!$pageFound)
                $content = "Page '$uri.html' not found!";
        }
        return $content;
    }

    static function go(array $context = [], string|null|bool $antiCache = true)
    {
        global $yAnalyzer;

        $context['baseURL'] = self::getBaseURL();

        self::initialize();

        $uri = self::getURI();

        if (strpos($uri, '?') > 0) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        $method = $_SERVER['REQUEST_METHOD'];
        $routeHandler = null;
        $routeHandlerDefinition = self::getRouteHandlerDefinition($uri, $method);
        if ($routeHandlerDefinition) {
            $routeHandler = $routeHandlerDefinition['handler'];
            $context['__routeHandler'] = $routeHandlerDefinition;

            $splittedURI = explode('/', $uri);
            $splittedFnPath = explode('/', $routeHandlerDefinition['fnAlias']);
            if (empty($splittedFnPath[0])) {
                array_shift($splittedFnPath);
            }

            $fnParams = [];
            $pNdx = 0;
            foreach ($splittedFnPath as $i => $fnPart) {
                if (substr($fnPart, 0, 1) == '*') {
                    if (isset($routeHandlerDefinition['parameters'][$pNdx])) {
                        $fnParams[$routeHandlerDefinition['parameters'][$pNdx]['name']] = $splittedURI[$i];
                    } else {
                        $fnParams["param_".($pNdx+1)] = $splittedURI[$i];
                    }
                    $pNdx++;
                }
            }

            $context['__' . $method] = array_merge($fnParams, $_POST);
            $context['__FILES'] = $_FILES;
        }

        if (self::clientExpectJSON()) {
            header('Content-Type: application/json', true);
        }

        if ($routeHandler) {
            $aBulletin = new \YeAPF\WebBulletin();
            $return_code = $routeHandler($aBulletin, $uri, $context, ...$fnParams);
            if ($return_code >= 200 && $return_code < 300) {
                
            }
            $aBulletin($return_code??500);
        } else {
            if (!empty($context['__json'])) {
                $content = ($context['__json']);
            } else {
                $content = self::renderPage($uri, $context, $antiCache);
            }

            if (is_array($content))
                $content = json_encode($content);

            // echo "<pre>";
            $headers = headers_list();
            $actualContentType = null;
            foreach ($headers as $header) {
                // echo "$header\n";
                if (strpos(strtolower($header), 'content-type:') === 0) {
                    $header = substr($header, 14);
                    $actualContentType = explode(';', $header)[0];
                }
            }

            // die("actualContentType: $actualContentType</pre>");
            $processableContentTypes = ['application/json', 'text/plain', 'text/html', 'text/markdown'];
            if ($actualContentType !== null && in_array($actualContentType, $processableContentTypes)) {
                $content = $yAnalyzer->do($content, $context);
            }
            // $content = $yAnalyzer->do($content, $context);

            if ($antiCache) {
                $content = self::applyAntiCache($content, $antiCache);
            }

            if ('devel' != ($context['mode'] ?? 'devel')) {
                $content = preg_replace('/^ +/m', '', $content);
                $content = preg_replace('/\n/m', ' ', $content);
            }
            // $content = str_replace("#(page_content)", $page_content, $content);
            echo $content;
        }
    }
}
