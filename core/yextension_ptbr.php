<?php
/**
 * This is an extension where brazilian specific stuff can be placed.
 * For example: formatting well known documents as CNPJ, CPF, CEP
 */
class yextension_ptbr {

  /**
   * It formats the CNPJ.
   *
   * CNPJ means National Registry of Legal Entities.
   * It has a very well known format.
   * In order to waste less space in storage, is a common practice to
   *  remove non digits from the string, so it needs to be formated
   *  again in order to be shown
   *
   * @param      array  $params  A YeAPF parameters structure
   *                             0 - Unformatted CNPJ
   *
   * @return     string  Formatted CNPJ
   */
  public function _CNPJ($params) {
    $that = $params['caller'];
    $ret  = '';
    $cnpj = $that->getParamValue($params, 0, 0);
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    while (strlen($cnpj) < 14) {
      $cnpj = "0$cnpj";
    }

    $ret = substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' .
           substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' .
           substr($cnpj, 12, 2);
    return $ret;
  }

  /**
   * If formats the CPF
   *
   * CPF is the brazilian taxpayer registry number.
   * Similar to CNPJ is common to store just the numbers in the database
   * and then, the number is formatted in order to display this
   * information for the user.
   *
   * @param      array  $params  A YeAPF parameters structure
   *                             0 - Unformatted CPF
   *
   * @return     string  Formatted CPF
   */
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

  /**
   * Formats the CEP
   *
   * CEP means Postal Zip Code
   *
   * @param      array  $params  A YeAPF parameters structure
   *                             0 - Unformatted CEP
   *
   * @return     string  Formatted CEP
   */
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

/**
 * The yextension_ptbr was built to be adopted by yAnalyzer
 */
$yAnalyzer->adoptClass("yextension_ptbr");
