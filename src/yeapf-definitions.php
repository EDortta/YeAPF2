<?php

/**
 * @author Esteban Dortta
 * @version 2
 * @package YeAPF
 */
define('YeAPF_LOG_DEBUG', 100);
define('YeAPF_LOG_INFO', 200);
define('YeAPF_LOG_NOTICE', 300);
define('YeAPF_LOG_WARNING', 400);
define('YeAPF_LOG_ERROR', 500);
define('YeAPF_LOG_CRITICAL', 600);
define('YeAPF_LOG_ALERT', 700);
define('YeAPF_LOG_EMERG', 800);

/*****************************************/
/* KEY-DATA */

/**
 * Basic types for YeAPF
 */
define('YeAPF_TYPE_STRING', 'string');
define('YeAPF_TYPE_INT', 'integer');
define('YeAPF_TYPE_FLOAT', 'float');
define('YeAPF_TYPE_BOOL', 'boolean');
define('YeAPF_TYPE_DATE', 'date');
define('YeAPF_TYPE_TIME', 'time');
define('YeAPF_TYPE_DATETIME', 'datetime');
define('YeAPF_TYPE_BYTES', 'bytes');

// define("YeAPF_TYPE_ARRAY", "array");
// define("YeAPF_TYPE_OBJECT", "object");

/**
 * Exception codes
 */
define('YeAPF_INVALID_SPACE_NAME', 0x00000001);
define('YeAPF_METHOD_NOT_IMPLEMENTED', 0x00000002);

define('YeAPF_DATA_EXCEPTION_BASE', 0x00000100);
define('YeAPF_CONNECTION_BASE', 0x00000200);
define('YeAPF_COLLECTION_BASE', 0x00000300);
define('YeAPF_EYESHOT_BASE', 0x00000400);
define('YeAPF_SERVICE_BASE', 0x00000500);
define('YeAPF_SECURITY_BASE', 0x00000600);

define('YeAPF_INVALID_KEY_TYPE', YeAPF_DATA_EXCEPTION_BASE + 1);
define('YeAPF_INVALID_KEY_VALUE', YeAPF_DATA_EXCEPTION_BASE + 2);
define('YeAPF_NULL_NOT_ALLOWED', YeAPF_DATA_EXCEPTION_BASE + 3);
define('YeAPF_INVALID_DATE_TYPE', YeAPF_DATA_EXCEPTION_BASE + 4);
define('YeAPF_INVALID_DATE_VALUE', YeAPF_DATA_EXCEPTION_BASE + 5);
define('YeAPF_VALUE_OUT_OF_RANGE', YeAPF_DATA_EXCEPTION_BASE + 6);
define('YeAPF_VALUE_TOO_LONG', YeAPF_DATA_EXCEPTION_BASE + 7);
define('YeAPF_LENGTH_IS_REQUIRED', YeAPF_DATA_EXCEPTION_BASE + 8);
define('YeAPF_PROTOBUF_ORDER_IS_NOT_UNIQUE', YeAPF_DATA_EXCEPTION_BASE + 9);
define('YeAPF_UNIMPLEMENTED_KEY_TYPE', YeAPF_DATA_EXCEPTION_BASE + 10);
define('YeAPF_VALUE_DOES_NOT_SATISFY_REGEXP', YeAPF_DATA_EXCEPTION_BASE + 11);

/**
 * Common regular expressions
 * These expressions are used to validate data when they are passed
 * to a SanitizedKeyData class instance or any descendant.
 */
define('YeAPF_DATE_REGEX', '/^(([12]\d{3})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))/');
define('YeAPF_DATETIME_REGEX', '/^(([12]\d{3})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))[ T]{1}([0-2]{1}[0-9]{1}):([0-5]{1}[0-9]{1}):([0-5]{1}[0-9]{1})[Z]{0,}$/');
define('YeAPF_TIME_REGEX', '/^([0-2]{1}[0-9]{1}):([0-5]{1}[0-9]{1}):([0-5]{1}[0-9]{1})[Z]{0,}$/');

define('YeAPF_INT_REGEX', '/^([0-9]+$)/');
define('YeAPF_FLOAT_REGEX', '/^([0-9]+)\.([0-9]+)$/');
define('YeAPF_BOOL_REGEX', '/^(true|false)$/i');
define('YeAPF_STRING_REGEX', '/^[^\p{C}]*$/u');

define('YeAPF_EMAIL_REGEX', '/^[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/');
define('YeAPF_UUID_REGEX', '/^[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}$/');

/**
 * Common sed expressions
 * These exxpressions are used to format the data in a
 * SanitizedKeyData instance or descendant
 * There are two ways to format the data:
 *   1. input: when the data is passed to a SanitizedKeyData instance
 *   2. output: when the data is returned from a SanitizedKeyData instance
 * As YeAPF was written while living in Brazil, the Brazilian
 * regular expressions are used and tested. All the other ones need to be checked.
 * Of course, you can write your own sed expressions.
 */

// CANADA
define('YeAPF_SED_CA_IN_SIN', "/[^0-9]//");
define('YeAPF_SED_CA_OUT_SIN', "/(\d{3})(\d{3})(\d{3})/$1 $2 $3/");

define('YeAPF_SED_CA_IN_POSTAL_CODE', "/[^A-Za-z0-9]//");
define('YeAPF_SED_CA_OUT_POSTAL_CODE', "/([A-Za-z]\d[A-Za-z])\s*(\d[A-Za-z]\d)/$1 $2/");

// UNITED STATES OF AMERICA
define('YeAPF_SED_US_IN_SSN', "/[^0-9]//");
define('YeAPF_SED_US_OUT_SSN', "/(\d{3})(\d{2})(\d{4})/$1-$2-$3/");

define('YeAPF_SED_US_IN_EIN', "/[^0-9]//");
define('YeAPF_SED_US_OUT_EIN', "/(\d{3})(\d{2})(\d{4})/$1-$2-$3/");

define('YeAPF_SED_US_IN_ZIP_CODE', "/[^0-9]//");
define('YeAPF_SED_US_OUT_ZIP_CODE', "/(\d{5})(\d{4})?/$1-$2/");

// MEXICO
define('YeAPF_SED_MX_IN_CURP', "'/[^A-Z0-9]//");
define('YeAPF_SED_MX_OUT_CURP', "/([A-Z]{4})(\d{6})([HM])([A-Z]{5})(\d{2})/$1$2$3$4$5/");

define('YeAPF_SED_MX_IN_RFC', "'/[^A-Z0-9]//");
define('YeAPF_SED_MX_OUT_RFC', "/([A-Z]{4})(\d{6})([A-Z0-9]{3})/$1$2$3/");

define('YeAPF_SED_MX_IN_POSTAL_CODE', "/[^0-9]//");
define('YeAPF_SED_MX_OUT_POSTAL_CODE', "/(\d{5})/$1/");

// BRAZIL
define('YeAPF_SED_BR_IN_CNPJ', "/[^0-9]//");
define('YeAPF_SED_BR_OUT_CNPJ', "/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/$1.$2.$3\/$4-$5/");

define('YeAPF_SED_BR_IN_CPF', "/[^0-9]//");
define('YeAPF_SED_BR_OUT_CPF', "/(\d{3})(\d{3})(\d{3})(\d{2})/$1.$2.$3-$4/");

define('YeAPF_SED_BR_IN_POSTAL_CODE', "/[^0-9]//");
define('YeAPF_SED_BR_OUT_POSTAL_CODE', "/(\d{5})(\d{3})/$1-$2/");


// VENEZUELA
define('YeAPF_SED_VE_IN_CI', "/[^0-9]//");
define('YeAPF_SED_VE_OUT_CI', "/(\d{1})(\d{3})(\d{3})(\d{1})/$1.$2.$3-$4/");

define('YeAPF_SED_VE_IN_POSTAL_CODE', "/[^0-9]//");
define('YeAPF_SED_VE_OUT_POSTAL_CODE', "/(\d{4})/$1/");

// URUGUAY
define('YeAPF_SED_UY_IN_CI', "/[^0-9]//");
define('YeAPF_SED_UY_OUT_CI', "/(\d{2})(\d{5})(\d{1})/$1.$2-$3/");

define('YeAPF_SED_UY_IN_POSTAL_CODE', "/[^0-9]//");
define('YeAPF_SED_UY_OUT_POSTAL_CODE', "/(\d{5})/$1/");

// PARAGUAY
define('YeAPF_SED_PY_IN_CI', "/[^0-9]//");
define('YeAPF_SED_PY_OUT_CI', "/(\d{1})(\d{3})(\d{2})(\d{2})/$1.$2.$3-$4/");

define('YeAPF_SED_PY_IN_POSTAL_CODE', "/[^0-9]//");
define('YeAPF_SED_PY_OUT_POSTAL_CODE', "/(\d{4})/$1/");

// ARGENTINA
define('YeAPF_SED_AR_IN_DNI', "/[^0-9]//");
define('YeAPF_SED_AR_OUT_DNI', "/(\d{2})(\d{3})(\d{3})/$1.$2.$3/");

define('YeAPF_SED_AR_IN_POSTAL_CODE', "/[^0-9]//");
define('YeAPF_SED_AR_OUT_POSTAL_CODE', "/(\d{4})/$1/");

// CHILE
define('YeAPF_SED_CL_IN_RUT', "'/[^0-9Kk]//");
define('YeAPF_SED_CL_OUT_RUT', "/(\d{2})(\d{3})(\d{3})([0-9Kk])/$1.$2.$3-$4/");

define('YeAPF_SED_CL_IN_POSTAL_CODE', "/[^0-9]//");
define('YeAPF_SED_CL_OUT_POSTAL_CODE', "/(\d{7})/$1/");

/**************************/
/* CONNECTION */
define('YeAPF_PDO_CONNECTION', YeAPF_CONNECTION_BASE + 1);
define('YeAPF_REDIS_CONNECTION', YeAPF_CONNECTION_BASE + 2);

/**************************/
/* COLLECTIONS */

define('YeAPF_UNDEFINED_MODEL', YeAPF_COLLECTION_BASE + 1);
define('YeAPF_WRONG_MODEL_FORMAT', YeAPF_COLLECTION_BASE + 2);
define('YeAPF_JSON_FORMAT', YeAPF_COLLECTION_BASE + 3);
define('YeAPF_SQL_FORMAT', YeAPF_COLLECTION_BASE + 4);
define('YeAPF_PROTOBUF_FORMAT', YeAPF_COLLECTION_BASE + 5);
define('YeAPF_DOCUMENT_MODEL_NOT_SET', YeAPF_COLLECTION_BASE + 6);
define('YeAPF_UNKNOWN_EXPORTABLE_FORMAT', YeAPF_COLLECTION_BASE + 7);
define('YeAPF_PDO_NOT_CONNECTED', YeAPF_COLLECTION_BASE + 8);
define('YeAPF_ERROR_ADDING_COLUMN', YeAPF_COLLECTION_BASE + 9);
define('YeAPF_SAVE_CACHE_FIRST', YeAPF_COLLECTION_BASE + 10);
define('YeAPF_SAVE_CACHE_LAST', YeAPF_COLLECTION_BASE + 11);
define('YeAPF_INVALID_CACHE_MODE', YeAPF_COLLECTION_BASE + 12);
define('YeAPF_INVALID_DATA', YeAPF_COLLECTION_BASE + 13);
define('YeAPF_OBSOLETE_FUNCTION', YeAPF_COLLECTION_BASE + 14);
define('YeAPF_EMPTY_POOL', YeAPF_COLLECTION_BASE + 15);
define('YeAPF_ASSETS_FOLDER_NOT_WRITABLE', YeAPF_COLLECTION_BASE + 16);
define('YeAPF_UNKNOWN_DATA_TYPE', YeAPF_COLLECTION_BASE + 17);
define('YeAPF_ASSETS_FOLDER_NOT_READABLE', YeAPF_COLLECTION_BASE + 18);

/***************************/
/* EYESHOT */
define('YeAPF_INNER_JOIN', YeAPF_EYESHOT_BASE + 1);
define('YeAPF_LEFT_JOIN', YeAPF_EYESHOT_BASE + 2);
define('YeAPF_RIGHT_JOIN', YeAPF_EYESHOT_BASE + 3);
define('YeAPF_FULL_JOIN', YeAPF_EYESHOT_BASE + 4);

define('YeAPF_EQUALS', YeAPF_EYESHOT_BASE + 5);
define('YeAPF_NOT_EQUALS', YeAPF_EYESHOT_BASE + 6);
define('YeAPF_GREATER_THAN', YeAPF_EYESHOT_BASE + 7);
define('YeAPF_GREATER_THAN_OR_EQUAL', YeAPF_EYESHOT_BASE + 8);
define('YeAPF_LESS_THAN', YeAPF_EYESHOT_BASE + 9);
define('YeAPF_LESS_THAN_OR_EQUAL', YeAPF_EYESHOT_BASE + 10);
define('YeAPF_IN', YeAPF_EYESHOT_BASE + 11);
define('YeAPF_NOT_IN', YeAPF_EYESHOT_BASE + 12);

define('YeAPF_AND', YeAPF_EYESHOT_BASE + 13);
define('YeAPF_OR', YeAPF_EYESHOT_BASE + 14);
define('YeAPF_NOT', YeAPF_EYESHOT_BASE + 15);

define('YeAPF_MAIN_COLLECTION_ALREADY_DEFINED', YeAPF_EYESHOT_BASE + 16);
define('YeAPF_INVALID_NUMBER_OF_PARAMETERS', YeAPF_EYESHOT_BASE + 17);

/***************************/
/* SERVICE */
define('YeAPF_GET_DESCRIPTION', YeAPF_SERVICE_BASE + 1);
define('YeAPF_GET_OPERATION_ID', YeAPF_SERVICE_BASE + 2);
define('YeAPF_GET_RESPONSES', YeAPF_SERVICE_BASE + 3);
define('YeAPF_GET_CONSTRAINTS', YeAPF_SERVICE_BASE + 4);
define('YeAPF_GET_SECURITY', YeAPF_SERVICE_BASE + 5);
define('YeAPF_GET_PRIVATE_PATH_FLAG', YeAPF_SERVICE_BASE + 6);

/***************************/
/* SECURITY */
define('YeAPF_JWT_KEY_UNDEFINED', YeAPF_SECURITY_BASE + 1);
define('YeAPF_JWT_SECTION_IDENTIFIER_TOO_LONG', YeAPF_SECURITY_BASE + 2);
define('YeAPF_JWT_SECTION_IDENTIFIER_EMPTY', YeAPF_SECURITY_BASE + 3);
define('YeAPF_JWT_SIGNATURE_VERIFICATION_FAILED', YeAPF_SECURITY_BASE + 4);
define('YeAPF_JWT_SIGNATURE_VERIFICATION_OK', YeAPF_SECURITY_BASE + 5);
define('YeAPF_JWT_TOKEN_ALREADY_IN_BIN', YeAPF_SECURITY_BASE + 6);
define('YeAPF_JWT_EXPIRED', YeAPF_SECURITY_BASE + 7);
