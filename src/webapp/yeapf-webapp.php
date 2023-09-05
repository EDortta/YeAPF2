<?php
declare (strict_types = 1);
namespace YeAPF;

class WebApp {
    static $uri=null;
    static $folder=null;

    static function getURI() {
        if (null==self::$uri) {
            self::$folder = dirname($_SERVER['PHP_SELF']);
            $uri = $_SERVER['REQUEST_URI'];

            $uri = str_replace(self::$folder, '', $uri);

            self::$uri = substr($uri, 1);
        }
        return self::$uri;
    }

    static function generateRandomVariableName($length = 8): string {
        $bytes = random_bytes($length);
        $name = bin2hex($bytes);
        $name = preg_replace('/^[0-9]+/', '', $name);
        $name = substr($name, 0, $length);
        $name = 'ts_' . $name;
        return $name;
    }

    static function go($context, $antiCache=true) {
        global $yAnalyzer;

        $uri=self::getURI();

        $uri=explode("/", $uri);
        $entrance = array_shift($uri);
        $uri=implode("/", $uri);

        if (!file_exists("template/pages/$entrance/$entrance.html")) {
            $entrance = "404";
        }

        if (file_exists("template/pages/$entrance/$entrance.html")) {
            $content = file_get_contents("template/pages/$entrance/$entrance.html");
            $content = str_replace("../../", self::$folder."/template/", $content);

            $page_content="Content of 'pages/$uri.html'";

            if (file_exists("pages/$uri.html")) {
                $page_content = file_get_contents("pages/$uri.html");
            }
            $context['page_content'] = $page_content;

            $content = $yAnalyzer->do($content, $context);

            if ($antiCache) {
                $randNumber=mt_rand(1000, 9999);
                $randId=self::generateRandomVariableName();
                $content=preg_replace('/(\<script[\n\t ]*src=["\']{1,})(.*)(["\']{1,}(.*)\>)/i', '$1$2?'.$randId.'='.$randNumber.'$3', $content);
            }


            // $content = str_replace("#(page_content)", $page_content, $content);
            echo $content;
        }
    }
}
