<?php

require_once __DIR__.'/../yeapf-core.php';

/**
 * https://picandocodigo.github.io/ci_js/
 */
$cedulas=[
    "34043626" => [
        "UY",
        TRUE
    ],
    "79488918" => [
        "UY",
        TRUE
    ],
    "49077165H" => [
        "AR",
        TRUE
    ],
    "84814845E" => [
        "PE",
        TRUE
    ],
    "92500986" => [
        "UY",
        FALSE
    ],
    "29031009" => [
        "UY",
        FALSE
    ],
];
echo "UI Cédula\tRes.\tRes.Correto?\n";
foreach($cedulas as $cedula => $cedulaDef) {
    $pais = $cedulaDef[0];
    $resultadoEsperado = $cedulaDef[1];

    echo "$pais $cedula\t";
    $resultadoObtido = $customerDocumentChecker->validate($pais, $cedula);
    if ($resultadoObtido) {
      echo "SIM";
    } else {
      echo "NÃO";
    }
    if ($resultadoEsperado==$resultadoObtido)
      echo "\tCorrecto";
    else
      echo "\tErrado";
    echo "\n";
  }

