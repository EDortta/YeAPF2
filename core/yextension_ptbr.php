<?php

class yextension_ptbr {

  public function _CNPJ($params) {
    $that = $params['caller'];
    $ret  = '';
    $cnpj = $that->getParamValue($params, 0, 0);
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    // exemplo 70.106.552/0001-64
    // 70106552000164
    while (strlen($cnpj) < 14) {
      $cnpj = "0$cnpj";
    }

    $ret = substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
    return $ret;
  }

  public function _CPF($params) {
    $that = $params['caller'];
    $ret = '';
    $cpf = $that->getParamValue($params, 0, 0);
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    // exemplo 111.111.111-11
    // 11111111111
    while (strlen($cpf) < 11) {
      $cpf = "0$cpf";
    }

    $ret = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    return $ret;
  }

  public function _CEP($params) {
    $that = $params['caller'];

    $ret = '';
    $cep = $that->getParamValue($params, 0, 0);
    // die(print_r($params));
    $cep = preg_replace('/[^0-9]/', '', $cep);
    // 11.111-111
    // 11111111
    while (strlen($cep) < 8) {
      $cep = "0$cep";
    }

    $ret = substr($cep, 0, 2) . '.' . substr($cep, 2, 3) . '-' . substr($cep, 5, 3);
    return $ret;
  }
}

$yAnaliser->adoptClass("yextension_ptbr");
