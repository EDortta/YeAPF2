<?php declare(strict_types=1);

namespace YeAPF;

class TypeChecker
{
    static function getType($var)
    {
        $ret = YeAPF_TYPE_STRING; 
        
        if (preg_match('/^null$/i', $var)) {
            $ret = YeAPF_TYPE_NULL;
        }        
        elseif (preg_match('/^(true|false)$/i', $var)) {
            $ret = YeAPF_TYPE_BOOL;
        }        
        elseif (preg_match('/^-?\d+$/', $var)) {
            $ret = YeAPF_TYPE_INT;
        }        
        elseif (preg_match('/^-?\d*\.\d+$/', $var)) {
            $ret = YeAPF_TYPE_FLOAT;
        }        
        // elseif (preg_match('/^\[.*\]$/s', $var)) {
        //     $ret = YeAPF_TYPE_ARRAY;
        // }        
        elseif (preg_match('/^\{.*\}$/s', $var)) {
            $ret = YeAPF_TYPE_JSON;
        }

        return $ret;
    }

    static function getYeapfTypeNameFromType(string $type)
    {
        $ret = 'YeAPF_TYPE_NULL';
        switch ($type) {
            case YeAPF_TYPE_STRING:
              $ret = 'YeAPF_TYPE_STRING';
              break;
            case YeAPF_TYPE_INT:
                $ret = 'YeAPF_TYPE_INT';
                break;
            case YeAPF_TYPE_FLOAT:
                $ret = 'YeAPF_TYPE_FLOAT';
                break;
            case YeAPF_TYPE_BOOL:
                $ret = 'YeAPF_TYPE_BOOL';
                break;
            case YeAPF_TYPE_DATE:
                $ret = 'YeAPF_TYPE_DATE';
                break;
            case YeAPF_TYPE_TIME:
                $ret = 'YeAPF_TYPE_TIME';
                break;
            case YeAPF_TYPE_DATETIME:
                $ret = 'YeAPF_TYPE_DATETIME';
                break;
            case YeAPF_TYPE_BYTES:
                $ret = 'YeAPF_TYPE_BYTES';
                break;
            case YeAPF_TYPE_JSON:
                $ret = 'YeAPF_TYPE_JSON';
                break;
      
        }

        return $ret;

    }
}

