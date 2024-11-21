<?php

/**
 * @author Esteban Dortta
 * @version 2
 * @package YeAPF
 */
define('YeAPF_LOG_DEBUG', 99);
define('YeAPF_LOG_INFO', 199);
define('YeAPF_LOG_NOTICE', 299);
define('YeAPF_LOG_WARNING', 399);
define('YeAPF_LOG_ERROR', 499);
define('YeAPF_LOG_CRITICAL', 599);
define('YeAPF_LOG_ALERT', 699);
define('YeAPF_LOG_EMERG', 799);

define('YeAPF_LOG_USING_FILE', 0x01);
define('YeAPF_LOG_USING_DB', 0x02);
define('YeAPF_LOG_USING_CONSOLE', 0x04);
define('YeAPF_LOG_USING_SYSLOG', 0x08);

define('YeAPF_LOG_STYLE_AS_STRINGS', 0x0100);
define('YeAPF_LOG_STYLE_AS_JSON', 0x0200);
define('YeAPF_LOG_STYLE_AS_XML', 0x0400);
define('YeAPF_LOG_STYLE_AS_ONE_STRING', 0x0800);

define('YeAPF_LOG_TAG_SERVER', 'server');                // usually hostname and/or IP
define('YeAPF_LOG_TAG_SERVICE', 'service');              // service name
define('YeAPF_LOG_TAG_CLIENT', 'client');                // client IP
define('YeAPF_LOG_TAG_USER', 'user');                    // RFC1413
define('YeAPF_LOG_TAG_USERID', 'userid');                // .htaccess login
define('YeAPF_LOG_TAG_REQUEST_TIME', 'request_time');
define('YeAPF_LOG_TAG_REQUEST', 'request');              // GET /list/all ...
define('YeAPF_LOG_TAG_RESULT', 'result');                // 200
define('YeAPF_LOG_TAG_RESPONSE_SIZE', 'response_size');  // bytes
define('YeAPF_LOG_TAG_RESPONSE_TIME', 'response_time');  // ms
define('YeAPF_LOG_TAG_RESPONSE_ERROR', 'response_error');
define('YeAPF_LOG_TAG_REFERRER', 'referer');
define('YeAPF_LOG_TAG_USERAGENT', 'useragent');

/*****************************************/
/* KEY-DATA */

/** Basic types for YeAPF */
define('YeAPF_TYPE_NULL', 'NULL');
define('YeAPF_TYPE_STRING', 'STRING');
define('YeAPF_TYPE_INT', 'INTEGER');
define('YeAPF_TYPE_FLOAT', 'FLOAT');
define('YeAPF_TYPE_BOOL', 'BOOLEAN');
define('YeAPF_TYPE_DATE', 'DATE');
define('YeAPF_TYPE_TIME', 'TIME');
define('YeAPF_TYPE_DATETIME', 'DATETIME');
define('YeAPF_TYPE_BYTES', 'BYTES');
define('YeAPF_TYPE_JSON', 'JSON');

// define("YeAPF_TYPE_ARRAY", "array");
// define("YeAPF_TYPE_OBJECT", "object");

/** Bulletin internal types */
define('YeAPF_BULLETIN_OUTPUT_TYPE_JSON', 'json');
define('YeAPF_BULLETIN_OUTPUT_TYPE_XML', 'xml');
define('YeAPF_BULLETIN_OUTPUT_TYPE_HTML', 'html');
define('YeAPF_BULLETIN_OUTPUT_TYPE_CSV', 'csv');
define('YeAPF_BULLETIN_OUTPUT_TYPE_TXT', 'txt');
define('YeAPF_BULLETIN_OUTPUT_TYPE_JSONSTRING', 'jsonString');
define('YeAPF_BULLETIN_OUTPUT_TYPE_JSONFILE', 'jsonFile');
define('YeAPF_BULLETIN_OUTPUT_TYPE_BINARYFILE', 'binaryFile');
define('YeAPF_BULLETIN_REDIRECTION', 'redirection');

/** Exception codes */
define('YeAPF_UNDEFINED_EXCEPTION', 0x00);
define('YeAPF_INVALID_SPACE_NAME', 0x01);
define('YeAPF_METHOD_NOT_IMPLEMENTED', 0x02);

define('YeAPF_DATA_EXCEPTION_BASE', 0x0100);
define('YeAPF_CONNECTION_BASE', 0x0200);
define('YeAPF_COLLECTION_BASE', 0x0300);
define('YeAPF_EYESHOT_BASE', 0x0400);
define('YeAPF_SERVICE_BASE', 0x0500);
define('YeAPF_SECURITY_BASE', 0x0600);
define('YeAPF_EXPRESSION_BASE', 0x0700);
define('YeAPF_PLUGIN_BASE', 0x0800);

define('YeAPF_INVALID_KEY_TYPE', YeAPF_DATA_EXCEPTION_BASE + 1);
define('YeAPF_INVALID_KEY_VALUE', YeAPF_DATA_EXCEPTION_BASE + 2);
define('YeAPF_NULL_NOT_ALLOWED', YeAPF_DATA_EXCEPTION_BASE + 3);
define('YeAPF_INVALID_DATE_TYPE', YeAPF_DATA_EXCEPTION_BASE + 4);
define('YeAPF_INVALID_DATE_VALUE', YeAPF_DATA_EXCEPTION_BASE + 5);
define('YeAPF_INVALID_INT_VALUE', YeAPF_DATA_EXCEPTION_BASE + 6);
define('YeAPF_VALUE_OUT_OF_RANGE', YeAPF_DATA_EXCEPTION_BASE + 7);
define('YeAPF_VALUE_TOO_LONG', YeAPF_DATA_EXCEPTION_BASE + 8);
define('YeAPF_SANITIZED_VALUE_TOO_LONG', YeAPF_DATA_EXCEPTION_BASE + 9);
define('YeAPF_LENGTH_IS_REQUIRED', YeAPF_DATA_EXCEPTION_BASE + 10);
define('YeAPF_PROTOBUF_ORDER_IS_NOT_UNIQUE', YeAPF_DATA_EXCEPTION_BASE + 11);
define('YeAPF_UNIMPLEMENTED_KEY_TYPE', YeAPF_DATA_EXCEPTION_BASE + 12);
define('YeAPF_VALUE_DOES_NOT_SATISFY_REGEXP', YeAPF_DATA_EXCEPTION_BASE + 13);
define('YeAPF_INVALID_FLOAT_VALUE', YeAPF_DATA_EXCEPTION_BASE + 14);
define('YeAPF_INVALID_DATETIME_VALUE', YeAPF_DATA_EXCEPTION_BASE + 15);
define('YeAPF_INVALID_TIME_TYPE', YeAPF_DATA_EXCEPTION_BASE + 16);
define('YeAPF_INVALID_TIME_VALUE', YeAPF_DATA_EXCEPTION_BASE + 17);
define('YeAPF_INVALID_JSON_TYPE', YeAPF_DATA_EXCEPTION_BASE + 18);
define('YeAPF_INVALID_JSON_VALUE', YeAPF_DATA_EXCEPTION_BASE + 19);

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
define('YeAPF_GET_TAGS', YeAPF_SERVICE_BASE + 7);

/***************************/
/* SECURITY */
define('YeAPF_JWT_KEY_UNDEFINED', YeAPF_SECURITY_BASE + 1);
define('YeAPF_JWT_SECTION_IDENTIFIER_TOO_LONG', YeAPF_SECURITY_BASE + 2);
define('YeAPF_JWT_SECTION_IDENTIFIER_EMPTY', YeAPF_SECURITY_BASE + 3);
define('YeAPF_JWT_SIGNATURE_VERIFICATION_FAILED', YeAPF_SECURITY_BASE + 4);
define('YeAPF_JWT_SIGNATURE_VERIFICATION_OK', YeAPF_SECURITY_BASE + 5);
define('YeAPF_JWT_TOKEN_ALREADY_IN_BIN', YeAPF_SECURITY_BASE + 6);
define('YeAPF_JWT_EXPIRED', YeAPF_SECURITY_BASE + 7);
define('YeAPF_JWT_ALREADY_IN_BIN', YeAPF_SECURITY_BASE + 8);

/***************************/
/* EXPRESSIONS */
define('YeAPF_EXPRESSION_UNDEFINED', YeAPF_EXPRESSION_BASE + 1);
define('YeAPF_EXPRESSION_NOT_VALID', YeAPF_EXPRESSION_BASE + 2);
define('YeAPF_UNRECOGNIZED_VERB', YeAPF_EXPRESSION_BASE + 3);
define('YeAPF_UNRECOGNIZED_OPERATOR', YeAPF_EXPRESSION_BASE + 4);
define('YeAPF_INCOMPLETE_SED_EXPRESSION', YeAPF_EXPRESSION_BASE + 5);

/***************************/
/* PLUGINS */
define('YeAPF_PLUGIN_ERROR', YeAPF_PLUGIN_BASE + 1);

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
define('YeAPF_STRING_REGEX', '/^[^\p{C}]*$/');

define('YeAPF_URL_REGEX', '/^((https?|ftp|file):\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/');
define('YeAPF_IP_REGEX', '/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/');
define('YeAPF_MAC_REGEX', '/^([0-9a-fA-F]{2}[:]){5}([0-9a-fA-F]{2})$/');

define('YeAPF_ID_REGEX', '/^([0-9a-zA-Z_\-\.]+)$/');

define('YeAPF_EMAIL_REGEX', '/^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/');
define('YeAPF_UUID_REGEX', '/^[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}$/');

/**
 * Common sed expressions
 * See README.md at regexp folder for more information
 */
(
    function () {
        $regexpFolder = __DIR__ . '/regexp';
        $regexpFiles  = glob($regexpFolder . '/*.php');

        foreach ($regexpFiles as $file) {
            require_once $file;
        }
    }
)();
