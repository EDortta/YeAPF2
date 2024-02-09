<?php
namespace YeAPF;
class DebugLabels
{
    static private $labels = [];

    static public function set($label, $value=null, $file=null) {
        if (null===$value) {
            $value = (
            function (): int {
                return max(array_column(self::$labels, 'value')) * 2;
            })();
        }
        self::$labels[$label] = [
            'value' => $value,
            'file' => $file
        ];
    }

    static public function get($label): int
    {
        if (!isset(self::$labels[$label])) {
            return 0;
        } else {
            return self::$labels[$label]['value'];
        }
    }

    static public function getFileFromLabel($label): string 
    {
        if (!isset(self::$labels[$label])) {
            return '';
        } else {
            return self::$labels[$label]['file'];
        }
    }
}


DebugLabels::set('YTYPES', YTYPES, 'yTypes');
DebugLabels::set('YPARS', YPARS, 'yParser');
DebugLabels::set('YLOCK', YLOCK, 'yLock');
DebugLabels::set('YDATAF', YDATAF, 'yDataFiller');
DebugLabels::set('YGBTPS', YGBTPS, 'yGenerateBasicTypes');
DebugLabels::set('YANLYZ', YANLYZ, 'yAnalyzer');
DebugLabels::set('WEBAPP', WEBAPP, 'webapp');
DebugLabels::set('SNGLGR', SNGLGR, 'single-logger');
DebugLabels::set('REQST', REQST, 'request');
DebugLabels::set('PDOCON', PDOCON, 'pdo-connection');
DebugLabels::set('RDSCON', RDSCON, 'redis-connection');
DebugLabels::set('GRPCIF', GRPCIF, 'grpc-interface');
DebugLabels::set('HTSVCS', HTSVCS, 'http2-service');
DebugLabels::set('EYESHT', EYESHT, 'eyeshot');
DebugLabels::set('LIBR', LIBR, 'library');
DebugLabels::set('LOADER', LOADER, 'loader');
DebugLabels::set('I18N', I18N, 'i18n');
DebugLabels::set('JWT', JWT, 'jwt');
DebugLabels::set('CORE', CORE, 'core');
DebugLabels::set('DOCCHK', DOCCHK, 'document-checker');
DebugLabels::set('EXCEPT', EXCEPT, 'exception');
DebugLabels::set('CLSRSL', CLSRSL, 'class.result');
DebugLabels::set('COLLCS', COLLCS, 'collections');
DebugLabels::set('PERSIN', PERSIN, 'persistence-interface');
DebugLabels::set('CONN', CONN, 'connection');
DebugLabels::set('CNFG', CNFG, 'config');
DebugLabels::set('PLGINS', PLGINS, 'class.plugins');
DebugLabels::set('PLGTM', PLGTM, 'class.plugin-template');
DebugLabels::set('KYDATA', KYDATA, 'class.key-data');
DebugLabels::set('BULTIN', BULTIN, 'bulletin');
