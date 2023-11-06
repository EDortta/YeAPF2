<?php
namespace YeAPF;

class DataFiller
{
    static function y_rand($min = 0, $max = null)
    {
        if ($max === null)
            $max = getrandmax();
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $t = mt_rand($min, $max);
        } else {
            do {
                $f = fopen('/dev/urandom', 'r');
                if ($f)
                    break;
                else
                    sleep(1);
            } while (true);
            $t = $max / mt_rand(1, 64);
            $n = mt_rand(7, 21);
            while ($n > 0) {
                $n--;
                for ($i = 0; $i < 7; $i++) {
                    $x = ord(fread($f, 1));
                    $t += $x;
                }
            }
            fclose($f);
            $t = $min + fmod($t, ($max - $min + 1));
        }
        return $t;
    }

    static function _mod_($dividendo, $divisor)
    {
        return round($dividendo - (floor($dividendo / $divisor) * $divisor));
    }

    static function inventarCPF($compontos = false, $base = null)
    {
        $comBase = false;
        if ($base != null) {
            $base = preg_replace('/[^0-9]+/', '', $base);
            if (strlen($base) == 9) {
                $comBase = true;
                for ($i = 1; $i < 10; $i++) {
                    $var = 'n' . $i;
                    $$var = substr($base, $i - 1, 1);
                }
            }
        }

        if (!$comBase) {
            $n1 = intval(self::y_rand(0, 9));
            $n2 = intval(self::y_rand(0, 9));
            $n3 = intval(self::y_rand(0, 9));
            $n4 = intval(self::y_rand(0, 9));
            $n5 = intval(self::y_rand(0, 9));
            $n6 = intval(self::y_rand(0, 9));
            $n7 = intval(self::y_rand(0, 9));
            $n8 = intval(self::y_rand(0, 9));
            $n9 = intval(self::y_rand(0, 9));
        }

        $d1 = $n9 * 2 + $n8 * 3 + $n7 * 4 + $n6 * 5 + $n5 * 6 + $n4 * 7 + $n3 * 8 + $n2 * 9 + $n1 * 10;
        $d1 = 11 - (self::_mod_($d1, 11));
        if ($d1 >= 10) {
            $d1 = 0;
        }

        $d2 = $d1 * 2 + $n9 * 3 + $n8 * 4 + $n7 * 5 + $n6 * 6 + $n5 * 7 + $n4 * 8 + $n3 * 9 + $n2 * 10 + $n1 * 11;
        $d2 = 11 - (self::_mod_($d2, 11));
        if ($d2 >= 10) {
            $d2 = 0;
        }

        $retorno = '';
        if ($compontos == 1) {
            $retorno = '' . $n1 . $n2 . $n3 . '.' . $n4 . $n5 . $n6 . '.' . $n7 . $n8 . $n9 . '-' . $d1 . $d2;
        } else {
            $retorno = '' . $n1 . $n2 . $n3 . $n4 . $n5 . $n6 . $n7 . $n8 . $n9 . $d1 . $d2;
        }

        return $retorno;
    }

    static function inventarCNPJ($compontos = false)
    {
        $n1 = intval(self::y_rand(0, 9));
        $n2 = intval(self::y_rand(0, 9));
        $n3 = intval(self::y_rand(0, 9));
        $n4 = intval(self::y_rand(0, 9));
        $n5 = intval(self::y_rand(0, 9));
        $n6 = intval(self::y_rand(0, 9));
        $n7 = intval(self::y_rand(0, 9));
        $n8 = intval(self::y_rand(0, 9));
        $n9 = 0;
        $n10 = 0;
        $n11 = 0;
        $n12 = 1;
        $d1 = $n12 * 2 + $n11 * 3 + $n10 * 4 + $n9 * 5 + $n8 * 6 + $n7 * 7 + $n6 * 8 + $n5 * 9 + $n4 * 2 + $n3 * 3 + $n2 * 4 + $n1 * 5;
        $d1 = 11 - (self::_mod_($d1, 11));
        if ($d1 >= 10) {
            $d1 = 0;
        }

        $d2 = $d1 * 2 + $n12 * 3 + $n11 * 4 + $n10 * 5 + $n9 * 6 + $n8 * 7 + $n7 * 8 + $n6 * 9 + $n5 * 2 + $n4 * 3 + $n3 * 4 + $n2 * 5 + $n1 * 6;
        $d2 = 11 - (self::_mod_($d2, 11));
        if ($d2 >= 10) {
            $d2 = 0;
        }

        $retorno = '';
        if ($compontos == 1) {
            $retorno = '' . $n1 . $n2 . '.' . $n3 . $n4 . $n5 . '.' . $n6 . $n7 . $n8 . '/' . $n9 . $n10 . $n11 . $n12 . '-' . $d1 . $d2;
        } else {
            $retorno = '' . $n1 . $n2 . $n3 . $n4 . $n5 . $n6 . $n7 . $n8 . $n9 . $n10 . $n11 . $n12 . $d1 . $d2;
        }

        return $retorno;
    }

    static function isAssoc($arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    static function fillFieldsWithJunk($aElements)
    {
        $_scratch = array(
            't' => array(
                'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.',
                'Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi. Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.',
                'Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi.',
                'Nam liber tempor cum soluta nobis eleifend option congue nihil imperdiet doming id quod mazim placerat facer possim assum. Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat.',
                'Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis.',
                'At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, At accusam aliquyam diam diam dolore dolores duo eirmod eos erat, et nonumy sed tempor et et invidunt justo labore Stet clita ea et gubergren, kasd magna no rebum. sanctus sea sed takimata ut vero voluptua. est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat. ',
                'Consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.'
            ),
            'd' => array(
                'yahu.com',
                'hotmayl.com',
                'jmail.com',
                'yahu.com.nh',
                'hotmayl.com.nh',
                'jmail.com.nh'
            ),
            'p' => array(
                'http://',
                'https://',
                'ws://',
                'wss://',
                'ftp://'
            ),
            'mn' => array(
                'Alexandre',
                'Charles',
                'David',
                'Diego',
                'Diogo',
                'James',
                'John',
                'Luan ',
                'Marcelo',
                'Michael',
                'Raul',
                'Ricardo ',
                'Richard',
                'Robert',
                'Vicente',
                'William'
            ),
            'fn' => array(
                'Alexia',
                'Antonella',
                'Ayla ',
                'Barbara',
                'Caroline',
                'Clarice',
                'Elizabeth',
                'Jennifer',
                'Joana',
                'Kamilly',
                'Linda',
                'Lis ',
                'Maria Sophia',
                'Maria',
                'Mary',
                'Patricia',
                'Susan'
            ),
            'sn' => array(
                'Brown',
                'Davies',
                'Evans',
                'Jones',
                'Smith',
                'Taylor',
                'Williams',
                'Wilson',
                'Carvalho',
                'Gomes',
                'Lopes',
                'Martins',
                'Melo',
                'Mendes',
                'Nunes',
                'Rodrigues'
            ),
            'tcnpj' => ['MEI', 'EI', 'LTDA', 'SLU', 'SS', 'SA'],
            'ch' => 'qwertyuiopasdfghjklzxcvbnmQAZWSXEDCRFVTGBYHNUJMIKOLP0123456789',
            'n' => '0123456789'
        );

        if (!is_array($aElements)) {
            $aElements = array($aElements);
        }
        if (self::isAssoc($aElements)) {
            $aElements = array($aElements);
        }

        $ret = array();
        $i = null;
        $fieldType = null;
        $fieldId = null;
        $fieldValue = null;
        $maxLength = null;
        $classes = null;

        $genString = static function ($base, $minLen, $maxLen) {
            $ret = '';
            $n = null;
            $j = null;
            // echo "Len from $minLen to $maxLen ... ";
            $maxLen = self::y_rand(0,$maxLen);
            $maxLen = intval($maxLen + $minLen, 0);
            // echo "$maxLen\n";
            $j = 0;
            $maxLoop = 100;
            while (($j < $maxLen) && ($maxLoop > 0)) {
                $maxLoop--;
                $n = floor(mt_rand(0, count($base) - 1));
                if (strpos($ret, $base[$n]) === false) {
                    $ret .= $base[$n] . ' ';
                    $j += strlen($base[$n]);
                }
            }

            return trim($ret);
        };

        $genNumber = static function ($min, $max, $leftPaddingLen = 0) {
            $ret = '' . floor(mt_rand($min, $max));
            while (strlen($ret) < $leftPaddingLen) {
                $ret = '0' . $ret;
            }

            return $ret;
        };

        $classHasName = static function ($lClasses, $name) {
            $ret = false;
            $name = strtoupper($name);
            for ($c = 0; $c < count($lClasses); $c++) {
                $ret = $ret || (strpos($lClasses[$c], "$name" . '-') !== false) || ($lClasses[$c] == $name);
            }
            return $ret;
        };

        $cacheForNextField = [];
        $nameEnterpiseType = '';

        foreach ($aElements as $elem) {
            $fieldType = strtolower($elem['type']);
            $fieldId = $elem['id'];
            $maxLength = isset($elem['maxlength']) ? intval($elem['maxlength']) : 100;
            $maxLength = round(self::y_rand($maxLength * 0.25, $maxLength),0);
            // echo "maxLength: $maxLength\n";
            $lClasses = isset($elem['className']) ? explode(' ', $elem['className']) : array();
            for ($n = 0; $n < count($lClasses); $n++) {
                $lClasses[$n] = strtoupper($lClasses[$n]);
            }

            $fieldValue = '';
            if ($fieldId) {
                if (!empty($cacheForNextField[$fieldId])) {
                    $fieldValue = $cacheForNextField[$fieldId];
                    unset($cacheForNextField[$fieldId]);
                } else {
                    if (isset($elem['cached'])) {
                        if (!empty($cacheForNextField[$elem['cached']])) {
                            $fieldValue = $cacheForNextField[$elem['cached']];
                            unset($cacheForNextField[$elem['cached']]);
                        }
                    }
                }

                if (empty($fieldValue)) {
                    switch ($fieldType) {
                        case 'password':
                            $fieldValue = $genString($_scratch['ch'], 6, 15);
                            break;

                        case 'textarea':
                            $fieldValue = $genString($_scratch['t'], 1, 15 * $maxLength);
                            break;

                        case 'email':
                            $fieldValue = $genString($_scratch['mn'], 2, 3) . '@' . $genString($_scratch['d'], 1, 1);
                            break;

                        case 'date':
                            $fieldValue = 1 * $genNumber(-2208981600000, 2556064800000);
                            $fieldValue = new Date($fieldValue);
                            $fieldValue = substr($fieldValue->toISOString(), 0, 10);
                            break;

                        case 'color':
                        case 'datetime':
                        case 'datetime-local':
                        case 'month':
                            $fieldValue = 1 * $genNumber(1, 12);
                            break;

                        case 'number':
                        case 'range':
                            $fieldValue = 1 * $genNumber(1, 100);
                            break;

                        case 'tel':
                            $fieldValue = 1 * $genNumber(10, 52);
                            for ($aux = 0; $aux < 3; $aux++) {
                                $fieldValue .= ' ' . $genNumber(100, 999);
                            }

                            break;

                        case 'search':
                        case 'time':
                        case 'week':
                            $fieldValue = 1 * $genNumber(1, 52);
                            break;

                        case 'url':
                            $fieldValue = $genString($_scratch['p'], 1, 1) . $genString($_scratch['d'], 1, 1) . '.xyz';
                            break;

                        case 'radio':
                        case 'checkbox':
                            break;

                        case 'select-one':
                        case 'select-multi':
                            break;

                        case 'hidden':
                            $fielValue = '';
                            break;

                        default:
                            $canCut = true;
                            if ($classHasName($lClasses, 'password')) {
                                $fieldValue = $genString($_scratch['ch'], 6, 15);
                            } else if ($classHasName($lClasses, 'cpf')) {
                                $fieldValue = self::inventarCPF(true);
                            } else if ($classHasName($lClasses, 'cnpj')) {
                                $fieldValue = self::inventarCNPJ(true);
                                $randomIndex = array_rand($_scratch['tcnpj']);
                                $nameEnterpiseType = $_scratch['tcnpj'][$randomIndex];
                            } else if ($classHasName($lClasses, 'ie')) {
                                $fieldValue = $genString($_scratch['n'], 6, 12);
                            } else if ($classHasName($lClasses, 'cep')) {
                                if (file_exists(__DIR__ . '/.data/cep.csv')) {
                                    if (!is_dir(__DIR__ . '/.cache')) {
                                        mkdir(__DIR__ . '/.cache', 0777, true);
                                    }

                                    $cepList = file(__DIR__ . '/.data/cep.csv');
                                    array_shift($cepList);
                                    $cacheForNextField = [];
                                    $alreadyFetched = [];
                                    if (file_exists(__DIR__ . '/.cache/inexistent.json')) {
                                        $inexistent = json_decode(file_get_contents(__DIR__ . '/.cache/inexistent.json'));
                                        $alreadyFetched = $inexistent;
                                    } else {
                                        $inexistent = [];
                                    }

                                    do {
                                        $errorFlag = false;
                                        do {
                                            $cepRow = $cepList[array_rand($cepList)];
                                            $cepValues = explode(';', $cepRow);
                                            $cacheForNextField['uf'] = $cepValues[0];
                                            $cacheForNextField['min_cep'] = $cepValues[3];
                                            $cacheForNextField['max_cep'] = $cepValues[4];
                                            $fieldValue = mt_rand($cacheForNextField['min_cep'], $cacheForNextField['max_cep']);
                                            if (in_array($cacheForNextField['uf'], ['SP', 'RJ', 'MG', 'PR', 'SC', 'RS']))
                                                $fieldValue = floor($fieldValue / 100) * 100;
                                            else
                                                $fieldValue = floor($fieldValue / 1000) * 1000;
                                        } while (in_array($fieldValue, $alreadyFetched));
                                        $alreadyFetched[] = $fieldValue;

                                        echo "  $fieldValue\n";
                                        if (!file_exists(__DIR__ . "/.cache/$fieldValue.json")) {
                                            $url = "https://viacep.com.br/ws/$fieldValue/json/";
                                            // echo "URL: $url\n";
                                            $ch = curl_init();
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                                            $result = curl_exec($ch);
                                            curl_close($ch);
                                            // print_r($result);
                                            $resultData = json_decode($result, true);
                                            if (empty($resultData['erro']))
                                                file_put_contents(__DIR__ . "/.cache/$fieldValue.json", $result);
                                            else {
                                                $errorFlag = true;
                                                $inexistent[] = $fieldValue;
                                            }
                                        }
                                    } while ($errorFlag);

                                    file_put_contents(__DIR__ . '/.cache/inexistent.json', json_encode($inexistent));

                                    $cacheForNextField = json_decode(file_get_contents(__DIR__ . "/.cache/$fieldValue.json"), true);
                                    if (empty($cacheForNextField['logradouro'])) {
                                        $logradouros = file(__DIR__ . '/.data/personalidades.csv');
                                        $cacheForNextField['logradouro'] = preg_replace('/[^\x20-\x7E]/', '', $logradouros[array_rand($logradouros)]);
                                    }

                                    if (empty($cacheForNextField['bairro'])) {
                                        $bairros = file(__DIR__ . '/.data/bairros.csv');
                                        $bairro = explode(';', $bairros[array_rand($bairros)])[1];
                                        $cacheForNextField['bairro'] = preg_replace('/[^\x20-\x7E]/', '', $bairro);
                                    }
                                } else {
                                    $fieldValue = $genNumber(10, 99, 2);
                                    $fieldValue .= '.' . $genNumber(0, 999, 3);
                                    $fieldValue .= '-' . $genNumber(0, 999, 3);
                                }
                            } else if ($classHasName($lClasses, 'zip')) {
                                $fieldValue = $genNumber(0, 99999, 5);
                                $fieldValue .= '-' . $genNumber(0, 9999, 4);
                            } else {
                                if (($classHasName($lClasses, 'name')) || ($classHasName($lClasses, 'nome'))) {
                                    $canCut = false;
                                    if (($classHasName($lClasses, 'female')) || ($classHasName($lClasses, 'feminino'))) {
                                        $fieldValue = mb_strtolower($genString($_scratch['fn'], 1, $maxLength / 2));
                                        $fieldValue .= ' ' . mb_strtoupper($genString($_scratch['sn'], 1, $maxLength - strlen($fieldValue)));
                                    } else if (($classHasName($lClasses, 'male')) || ($classHasName($lClasses, 'masculino'))) {
                                        $fieldValue = mb_strtolower($genString($_scratch['mn'], 1, $maxLength / 2));
                                        $fieldValue .= ' ' . mb_strtoupper($genString($_scratch['sn'], 1, $maxLength - strlen($fieldValue)));
                                    } else {
                                        $fieldValue = mb_strtolower($genString(array_merge($_scratch['fn'], $_scratch['mn']), 1, $maxLength / 2));
                                        $fieldValue .= ' ' . mb_strtoupper($genString($_scratch['sn'], 1, $maxLength - strlen($fieldValue)));
                                    }
                                    if ($nameEnterpiseType>'') {
                                      $fieldValue = mb_strtoupper("$fieldValue - $nameEnterpiseType");
                                      $nameEnterpiseType="";
                                    }
                                } else {
                                    $fieldValue = $genString($_scratch['t'], 1, $maxLength);
                                }
                            }

                            if ($canCut) {
                                $fieldValue = substr($fieldValue, 0, $maxLength);
                            }
                            break;
                    }
                }

                $ret[$fieldId] = $fieldValue;

            }
        }
        return $ret;
    }
}
