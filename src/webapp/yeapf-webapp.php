<?php declare(strict_types=1);

namespace YeAPF;

class WebApp
{
    static $uri = null;
    static $folder = null;
    static $mode = null;
    static $uselessURI = 0;
    static $routes = [];

    public static function initialize()
    {
        if (null == self::$mode) {
            self::$mode = \YeAPF\YeAPFConfig::getSection('mode')->mode;
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
        $url .= dirname($_SERVER['DOCUMENT_URI']);
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
            $uri = $_SERVER['REQUEST_URI'];

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
        return (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
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
            $content = preg_replace($filesHookExpr, '$1$4?' . $antiCacheURI . '$5', $content ?? '');
        }

        return $content;
    }

    static function setRouteHandler($path, $method, $handler)
    {
        $path = ltrim($path, '/');
        $path = ltrim($path, '\/');
        if (!isset(self::$routes[$path])) {
            self::$routes[$path] = [];
        }
        if (!isset(self::$routes[$path][$method])) {
            self::$routes[$path][$method] = $handler;
        } else {
            throw new \YeAPF\YeAPFException('Route already exists');
        }
    }

    static function getRouteHandler($path, $method)
    {
        $ret = null;
        foreach (self::$routes as $pattern => $methods) {
            if (preg_match('/' . $pattern . '/', $path) && isset($methods[$method])) {
                $ret = $methods[$method];
                break;
            }
        }

        return $ret;
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

        $routeHandler = self::getRouteHandler($uri, $_SERVER['REQUEST_METHOD']);
        if ($routeHandler) {
            $content = $routeHandler($uri, $context);
        } else {
            if (self::clientExpectJSON()) {
                header('Content-Type: application/json');
            } else {
                header('Content-Type: text/html; charset=utf-8');
            }

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
                if (file_exists("pages/$uri/$uri.html")) {
                    $content = file_get_contents("pages/$uri/$uri.html");
                } else if (file_exists("pages/$uri.html")) {
                    $content = file_get_contents("pages/$uri.html");
                }
            }
        }

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
        $acceptedContentTypes = ['application/json', 'text/plain', 'text/html', 'text/markdown'];
        if ($actualContentType !== null && in_array($actualContentType, $acceptedContentTypes)) {
            // $content = $yAnalyzer->do($content, $context);
        }
        $content = $yAnalyzer->do($content, $context);

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
