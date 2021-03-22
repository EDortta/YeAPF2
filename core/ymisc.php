<?php

if (!function_exists("getFilenameExtension")) {
  function getFilenameExtension($filename) {
    return pathinfo($filename, PATHINFO_EXTENSION);
  }
}

if (!function_exists("replaceFilenameExtension")) {
  function replaceFilenameExtension($filename, $new_extension) {
    $info = pathinfo($filename);
    return $info['dirname'] . '/' . $info['filename'] . '.' . $new_extension;
  }
}

function isValidDocument($country, $document_type, $document) {

  $ret = false;

  $CPFCorreto = function ($cpf) {
    $nulos = array("12345678909", "11111111111", "22222222222", "33333333333",
      "44444444444", "55555555555", "66666666666", "77777777777",
      "88888888888", "99999999999", "00000000000");
    $cpf = preg_replace("/[^0-9]+/", "", $cpf);

    if ((in_array($cpf, $nulos)) || (strlen($cpf) < 11)) {
      return false;
    } else {
      $acum = 0;
      for ($i = 0; $i < 9; $i++) {
        $acum += $cpf[$i] * (10 - $i);
      }

      $x    = $acum % 11;
      $acum = ($x > 1) ? (11 - $x) : 0;
      if ($acum != $cpf[9]) {
        return false;
      } else {
        $acum = 0;
        for ($i = 0; $i < 10; $i++) {
          $acum += $cpf[$i] * (11 - $i);
        }

        $x    = $acum % 11;
        $acum = ($x > 1) ? (11 - $x) : 0;
        if ($acum != $cpf[10]) {
          return false;
        } else {
          return true;
        }
      }
    }
  };

  $CNPJCorreto = function ($cnpj) {
    // Deixa o CNPJ com apenas números
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

    // Garante que o CNPJ é uma string
    $cnpj = (string) $cnpj;

    // O valor original
    $cnpj_original = $cnpj;

    // Captura os primeiros 12 números do CNPJ
    $primeiros_numeros_cnpj = substr($cnpj, 0, 12);

    /**
     * Multiplicação do CNPJ
     *
     * @param string $cnpj Os digitos do CNPJ
     * @param int $posicoes A posição que vai iniciar a regressão
     * @return int O
     *
     */
    if (!function_exists('multiplicaCnpj')) {
      function multiplicaCnpj($cnpj, $posicao = 5) {
        // Variável para o cálculo
        $calculo = 0;

        // Laço para percorrer os item do cnpj
        for ($i = 0; $i < strlen($cnpj); $i++) {
          // Cálculo mais posição do CNPJ * a posição
          $calculo = $calculo + ($cnpj[$i] * $posicao);

          // Decrementa a posição a cada volta do laço
          $posicao--;

          // Se a posição for menor que 2, ela se torna 9
          if ($posicao < 2) {
            $posicao = 9;
          }
        }
        // Retorna o cálculo
        return $calculo;
      }
    }

    // Faz o primeiro cálculo
    $primeiro_calculo = multiplicaCnpj($primeiros_numeros_cnpj);

    // Se o resto da divisão entre o primeiro cálculo e 11 for menor que 2, o primeiro
    // Dígito é zero (0), caso contrário é 11 - o resto da divisão entre o cálculo e 11
    $primeiro_digito = ($primeiro_calculo % 11) < 2 ? 0 : 11 - ($primeiro_calculo % 11);

    // Concatena o primeiro dígito nos 12 primeiros números do CNPJ
    // Agora temos 13 números aqui
    $primeiros_numeros_cnpj .= $primeiro_digito;

    // O segundo cálculo é a mesma coisa do primeiro, porém, começa na posição 6
    $segundo_calculo = multiplicaCnpj($primeiros_numeros_cnpj, 6);
    $segundo_digito  = ($segundo_calculo % 11) < 2 ? 0 : 11 - ($segundo_calculo % 11);

    // Concatena o segundo dígito ao CNPJ
    $cnpj = $primeiros_numeros_cnpj . $segundo_digito;

    // Verifica se o CNPJ gerado é idêntico ao enviado
    if ($cnpj === $cnpj_original) {
      return true;
    }
  };

  $CNHCorreto = function ($cnh) {
    $cnh = preg_replace("/[^0-9]+/", "", $cnh);
    $ret = false;
    if (strlen($cnh) == 11) {
      //15343191005
      $cnh_forn  = substr($cnh, 0, 9);
      $dig_forn  = substr(9, 2);
      $incr_dig2 = 0;
      $soma      = 0;
      $mult      = 9;
      for ($j = 0; $j < 9; $j++) {
        $soma += intval(substr($cnh_forn, $j, 1)) * $mult;
        $mult--;
      }
      $digito1 = $soma % 11;
      if ($digito1 == 10) {
        $incr_dig2 = -2;
      }

      if ($digito1 > 9) {
        $digito1 = 0;
      }

      $soma = 0;
      $mult = 1;
      for ($j = 0; $j < 8; $j++) {
        $soma += intval(substr($cnh_forn($j, 1))) * $mult;
        $mult++;
      }
      $aux = ($soma % 11 + $incr_dig2);
      if ($aux < 0) {
        $digito2 = 11 + $aux;
      }

      if ($aux >= 0) {
        $digito2 = $aux;
      }

      if ($digito2 > 9) {
        $digito2 = 0;
      }

      $dig_enc = $digito1 . $digito2;
      $ret     = $dig_enc == $dig_forn;
    }
    return $ret;
  };

  $country       = trim(mb_strtolower($country));
  $document_type = trim(mb_strtolower($document_type));
  switch ("$country.$document_type") {
  case 'br.cpf':
    $ret = $CPFCorreto($document);
    break;

  case 'br.cnpj':
    $ret = $CNPJCorreto($document);
    break;

  case 'br.cnh':
    $ret = $CNHCorreto($document);
    break;

  default:
    _die("'$country.$document_type' not implemented");
  }
}

function isValidEmail($email) {
  $ret = false;
  preg_match('/([a-zA-Z_0-9]{1}[a-zA-Z_\-0-9\.]*)@([a-zA-Z_0-9]{1}[a-zA-Z_\-0-9\.]*)/', $email, $output_array);
  if ($output_array) {
    $ret = ($output_array[0] == $email) && (strpos($output_array[2], '.') > 0);
  }

  return $ret;
}

function isValidURL($url) {
  $ret          = false;
  $path         = parse_url($url, PHP_URL_PATH);
  $encoded_path = array_map('urlencode', explode('/', $path));
  $url          = str_replace($path, implode('/', $encoded_path), $url);

  preg_match('/(http[s]?:\/\/)?[a-zA-Z0-9_.]*/', $url, $output_array);

  if ($output_array) {
    $ret = ($output_array[0] == $url) && (strpos($output_array[0], '.') > 0);
  }

  return $ret;
}

function isValidDate($date, $format = 'Y-m-d') {
  $d = DateTime::createFromFormat($format, $date);
  return $d && $d->format($format) === $date;
}

function onlyNumbers($value, $extra = '') {
  $value = preg_replace("/[^0-9" . addslashes($extra) . "]/", "", $value);
  return $value;
}

/**
 * Esta função tem por propósito preparar a lista de elementos
 * separada por vírgulas em um string de elementos encerrados em
 * aspas separados por vírgulas.
 * Isso é util para fazer consultas usando o operador sql 'in'
 **/
function createSetOfElements($elements) {
  $ret   = "";
  $elems = explode(",", $elements);
  foreach ($elems as $key) {
    if ($ret > '') {
      $ret .= ", ";
    }

    $ret .= "'" . trim($key) . "'";
  }
  return $ret;
}

/**
 * Isto é muito grosseiro.
 * Como o costume (diferente do código civil) faz com que
 * os brasileiros coloquem o sobrenome do pai no final, podemos
 * - pelo menos - puxar o patronimico com confiança já que
 * ocupa a útlima posição
 * @param string $nomeCompleto
 * @return array
 */
function splitName($name) {
  $name = trim($name);
  $n    = strrpos($name, " ");
  $ret  = array(
    trim(substr($name, 0, $n)),
    trim(substr($name, $n)),
  );
  return $ret;
}

function reducePictureSize($fileName, $desiredMaxSizeMB = 5) {
  $ret = _emptyRet();

  clearstatcache();

  $folder = dirname($fileName);
  if (is_writable($folder)) {
    $size1 = filesize($fileName) / 1024 / 1024;
    if ($size1 > $desiredMaxSizeMB) {

      $backupFilename = replaceFilenameExtension("$fileName", ".backup." . getExtension($fileName));

      if (copy($fileName, "$backupFilename")) {
        $info          = getimagesize($fileName);
        $podeContinuar = false;
        if (_getValue($info, 'mime', 'UNKNOWN') == 'image/jpeg') {
          $image         = imagecreatefromjpeg($fileName);
          $podeContinuar = true;
        } elseif (_getValue($info, 'mime', 'UNKNOWN') == 'image/gif') {
          $image         = imagecreatefromgif($fileName);
          $podeContinuar = true;
        } elseif (_getValue($info, 'mime', 'UNKNOWN') == 'image/png') {
          $image         = imagecreatefrompng($fileName);
          $podeContinuar = true;
        } else {
          _record($ret, "Arquivo '" . _getValue($info, 'mime', 'UNKNOWN') . "' não pode ser reduzido");
        }

        if ($podeContinuar) {
          $newFileName = replaceFilenameExtension("$fileName", ".new." . getExtension($fileName));
          imagejpeg($image, "$newFileName", 90);
          $size2 = filesize("$newFileName") / 1024 / 1024;
          if ($size1 < $size2) {
            /* descarto porque não ficou com um tamanho menor */
            unlink("$newFileName");
            $ret['analised'][] = "Descarto $newFileName porque $size1 é menor que $size2";
          } else {
            /* mantenho porque esta versão é menor que a original */
            replaceFilenameExtension($fileName, ".jpg");
            rename("$newFileName", $fileName);
            $image             = imagecreatefromjpeg($fileName);
            $ret['analised'][] = "Mantenho $newFileName porque $size1 é maior igual que $size2";
          }
          $ret['new_filename'] = $fileName;
          clearstatcache();
          $size1 = filesize($fileName) / 1024 / 1024;
          if ($size1 > $desiredMaxSizeMB) {
            $info      = getimagesize($fileName);
            $width     = $info[0];
            $height    = $info[1];
            $maxWidth  = 1280;
            $maxHeight = 960;
            $canReduce = false;
            if ($maxWidth < $width) {
              $ret['analised'][] = "A largura compromete o tamanho da imagem";
              $newWidth          = $maxWidth;
              $newHeight         = $height * $newWidth / $width;
              $canReduce         = true;
            } else if ($maxHeight < $height) {
              $ret['analised'][] = "A altura compromete o tamanho da imagem";
              $newHeight         = $maxHeight;
              $newWidth          = $width * $newHeight / $height;
            } else {
              _record($ret, "Imagem já está dentro dos limites de tamanho");
            }

            if ($canReduce) {
              $ret['analised'][] = "Reduzão para $newWidth x $newHeight";
              $dst               = imagecreatetruecolor($newWidth, $newHeight);
              imagecopyresampled($dst, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
              imagejpeg($dst, "$fileName", 100);
            }
          }
        }

      } else {
        _record($ret, "Arquivo não pôde ser copiado");
      }
    } else {
      $ret['ret_code'] = 200;
    }
  } else {
    _record($ret, "Pasta não pode ser escrita");
  }
  return $ret;
}

function healFilename($filename) {
  $ASCIIFileName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $filename);
  $ASCIIFileName = preg_replace('/\s+/', '_', $ASCIIFileName);
  $ASCIIFileName = preg_replace('/[^0-9.a-zA-Z_\-\/]+/', '', $ASCIIFileName);
  return $ASCIIFileName;
}

/**
 * Redireciona para o download de um arquivo
 **/
function downloadFile($path = '', $filename) {

  if ($path === '') {
    return;
  }

  $file = realpath($GLOBALS['CFGSiteFolder']) . "/" . $path;
  // check file exists

  if (file_exists($file)) {
    // get file content
    $data = file_get_contents($file);
    //force download

    $filesize = strlen($data);
    // Set the default MIME type to send
    $mime = 'application/octet-stream';

    $x         = explode('.', $filename);
    $extension = end($x);

    /* It was reported that browsers on Android 2.1 (and possibly older as well)
     * need to have the filename extension upper-cased in order to be able to
     * download it.
     *
     * Reference: http://digiblog.de/2011/04/19/android-and-the-download-file-headers/
     */
    if (count($x) !== 1 && isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Android\s(1|2\.[01])/', $_SERVER['HTTP_USER_AGENT'])) {
      $x[count($x) - 1] = strtoupper($extension);
      $filename         = implode('.', $x);
    }

    if ($data === null && ($fp = @fopen($filepath, 'rb')) === false) {
      return;
    }

    // Clean output buffer
    if (ob_get_level() !== 0 && @ob_end_clean() === false) {
      @ob_clean();
    }

    // Generate the server headers
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . $filesize);
    header('Cache-Control: private, no-transform, no-store, must-revalidate');

    // If we have raw data - just dump it
    if ($data !== null) {
      exit($data);
    }

    // Flush 1MB chunks of data
    while (!feof($fp) && ($data = fread($fp, 1048576)) !== false) {
      echo $data;
    }

    fclose($fp);
    exit;
  }
}

function formatacnpj($cnpj) {
  $bloco_1            = substr($cnpj, 0, 2);
  $bloco_2            = substr($cnpj, 2, 3);
  $bloco_3            = substr($cnpj, 5, 3);
  $bloco_4            = substr($cnpj, 8, 4);
  $digito_verificador = substr($cnpj, -2);
  $cnpj_formatado     = $bloco_1 . "." . $bloco_2 . "." . $bloco_3 . "/" . $bloco_4 . "-" . $digito_verificador;
  return $cnpj_formatado;
}
