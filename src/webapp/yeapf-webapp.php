<?php declare(strict_types=1);

namespace YeAPF;

class WebApp
{
    static $uri = null;
    static $folder = null;
    static $mode = null;
    static $uselessURI = 0;

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

                _log("minFile: $minFile");

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
            $content = preg_replace($filesHookExpr, '$1$4?' . $antiCacheURI . '$5', $content);
        }

        return $content;
    }

    static function go(array $context = [], string|null|bool $antiCache = true)
    {
        global $yAnalyzer;

        self::initialize();

        if (self::clientExpectJSON()) {
            header('Content-Type: application/json');
            echo json_encode($context);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            $uri = self::getURI();

            $entrance = '';
            $uri = explode('/', $uri);
            $c = self::$uselessURI;
            while ($c > 0) {
                $entrance = array_shift($uri) . '/';
                $c--;
            }
            $entrance = rtrim($entrance, '/');

            $uri = implode('/', $uri);
            $content = '';

            if (!file_exists("template/pages/$entrance/$entrance.html")) {
                $entrance = '404';
            }

            $context['mode'] = self::$mode;

            if (file_exists("template/pages/$entrance/$entrance.html")) {
                $content = file_get_contents("template/pages/$entrance/$entrance.html");
                $content = str_replace('../../', self::$folder . '/template/', $content);

                $page_content = "Content of 'pages/$uri.html'";

                if (file_exists("pages/$uri.html")) {
                    $page_content = file_get_contents("pages/$uri.html");
                    if ($antiCache) {
                        // $page_content = self::applyAntiCache($page_content, $antiCache);
                    }
                }
                $context['page_content'] = $page_content;
            } else {
                if (file_exists("pages/$uri.html")) {
                    $content = file_get_contents("pages/$uri.html");
                }
            }

            $content = $yAnalyzer->do($content, $context);

            if ($antiCache) {
                $content = self::applyAntiCache($content, $antiCache);
            }

            if ('devel' != $context['mode']) {
                $content = preg_replace('/^ +/m', '', $content);
                $content = preg_replace('/\n/m', ' ', $content);
            }
            // $content = str_replace("#(page_content)", $page_content, $content);
            echo $content;
        }

    }
}
