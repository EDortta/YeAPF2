<?php declare(strict_types=1);

namespace YeAPF;

class WebApp
{
    static $uri = null;
    static $folder = null;
    static $mode = null;

    public static function initialize()
    {
        if (null == self::$mode) {
            self::$mode = \YeAPF\YeAPFConfig::getSection('mode')->mode;
        }
    }

    static function getURI()
    {
        if (null == self::$uri) {
            self::$folder = dirname($_SERVER['PHP_SELF']);
            $uri = $_SERVER['REQUEST_URI'];

            $uri = str_replace(self::$folder, '', $uri);

            if (substr($uri, 0, 1) == '/') {
                $uri = substr($uri, 1);
            }

            self::$uri = substr($uri, 1);
        }
        return self::$uri;
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

        /*
         * $filesHookExpr = '/(\<script[\n\t ]*src=["\']{1,})([^"\']*)(["\']{1,}(.*)\>)/i';
         * if (self::$mode != 'devel') {
         *     $content = preg_replace_callback($filesHookExpr, function ($matches) use ($randId, $randNumber) {
         *         $file = $matches[2];
         *         $minFile = preg_replace('/\.js$/', '.min.js', $file);
         *
         *         $minFile = explode('/', $minFile);
         *         $folder = '';
         *         do {
         *             $particle = array_shift($minFile);
         *             $folder .= $particle . '/';
         *         } while ($particle == '');
         *         $minFile = implode('/', $minFile);
         *
         *         // Check if the .min file exists
         *         if (file_exists($minFile)) {
         *             return $matches[1] . $folder . $minFile . '?' . $antiCacheURI . $matches[3];
         *         } else {
         *             return $matches[0];
         *         }
         *     }, $content);
         * } else {
         *     $content = preg_replace($filesHookExpr, '$1$2?' . $antiCacheURI . '$3', $content);
         * }
         */
        // $filesHookExpr = '/(\<(script|link)[\n\t ]*(src|href)=["\']{1,})([^"\']*)(["\']{1,}(.*)\>)/i';
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

    static function go($context, $antiCache = true)
    {
        global $yAnalyzer;

        self::initialize();

        $uri = self::getURI();

        $uri = explode('/', $uri);
        $entrance = array_shift($uri);
        $uri = implode('/', $uri);

        if (!file_exists("template/pages/$entrance/$entrance.html")) {
            $entrance = '404';
        }
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
            $context['mode'] = self::$mode;

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
