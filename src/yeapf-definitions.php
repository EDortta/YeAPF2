<?php

/**
 * @author Esteban Dortta
 * @version 2
 * @package YeAPF
 */

/*****************************************/
/* KEY-DATA */

/**
 * Basic types for YeAPF
 */
define("YeAPF_TYPE_STRING", "string");
define("YeAPF_TYPE_INT", "int");
define("YeAPF_TYPE_FLOAT", "float");
define("YeAPF_TYPE_BOOL", "bool");
define("YeAPF_TYPE_DATE", "date");
define("YeAPF_TYPE_TIME", "time");
define("YeAPF_TYPE_DATETIME", "datetime");
define("YeAPF_TYPE_BYTES", "bytes");

// define("YeAPF_TYPE_ARRAY", "array");
// define("YeAPF_TYPE_OBJECT", "object");

/**
 * Exception codes
 */
define("YeAPF_INVALID_KEY_TYPE", YeAPF_DATA_EXCEPTION_BASE + 1);
define("YeAPF_INVALID_KEY_VALUE", YeAPF_DATA_EXCEPTION_BASE + 2);
define("YeAPF_NULL_NOT_ALLOWED", YeAPF_DATA_EXCEPTION_BASE + 3);
define("YeAPF_INVALID_DATE_TYPE", YeAPF_DATA_EXCEPTION_BASE + 4);
define("YeAPF_INVALID_DATE_VALUE", YeAPF_DATA_EXCEPTION_BASE + 5);
define("YeAPF_VALUE_OUT_OF_RANGE", YeAPF_DATA_EXCEPTION_BASE + 6);
define("YeAPF_VALUE_TOO_LONG", YeAPF_DATA_EXCEPTION_BASE + 7);
define("YeAPF_LENGTH_IS_REQUIRED", YeAPF_DATA_EXCEPTION_BASE + 8);
define("YeAPF_PROTOBUF_ORDER_IS_NOT_UNIQUE", YeAPF_DATA_EXCEPTION_BASE + 9);
define("YeAPF_UNIMPLEMENTED_KEY_TYPE", YeAPF_DATA_EXCEPTION_BASE + 10);

/**
 * Common regular expressions
 */
define("YeAPF_DATE_REGEX", '/^(([12]\d{3})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))/');
define("YeAPF_DATETIME_REGEX", '/^(([12]\d{3})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))[ T]{1}([0-2]{1}[0-9]{1}):([0-5]{1}[0-9]{1}):([0-5]{1}[0-9]{1})[Z]{0,}$/');
define("YeAPF_TIME_REGEX", '/^([0-2]{1}[0-9]{1}):([0-5]{1}[0-9]{1}):([0-5]{1}[0-9]{1})[Z]{0,}$/');

define("YeAPF_INT_REGEX", '/^([0-9]+$)/');
define("YeAPF_FLOAT_REGEX", '/^([0-9]+)\.([0-9]+)$/');
define("YeAPF_BOOL_REGEX", '/^(true|false)$/gi');

define("YeAPF_EMAIL_REGEX", '/^[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/');
define("YeAPF_UUID_REGEX", '/^[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}$/');

/**************************/
/* CONNECTION */
define("YeAPF_PDO_CONNECTION", YeAPF_CONNECTION_BASE + 1);
define("YeAPF_REDIS_CONNECTION", YeAPF_CONNECTION_BASE + 2);

/**************************/
/* COLLECTIONS */

define("YeAPF_UNDEFINED_MODEL", YeAPF_COLLECTION_BASE + 1);
define("YeAPF_WRONG_MODEL_FORMAT", YeAPF_COLLECTION_BASE + 2);
define("YeAPF_JSON_FORMAT", YeAPF_COLLECTION_BASE + 3);
define("YeAPF_SQL_FORMAT", YeAPF_COLLECTION_BASE + 4);
define("YeAPF_PROTOBUF_FORMAT", YeAPF_COLLECTION_BASE + 5);
define("YeAPF_DOCUMENT_MODEL_NOT_SET", YeAPF_COLLECTION_BASE + 6);
define("YeAPF_UNKNOWN_EXPORTABLE_FORMAT", YeAPF_COLLECTION_BASE + 7);
define("YeAPF_PDO_NOT_CONNECTED", YeAPF_COLLECTION_BASE + 8);
define("YeAPF_ERROR_ADDING_COLUMN", YeAPF_COLLECTION_BASE + 9);
define("YeAPF_SAVE_CACHE_FIRST", YeAPF_COLLECTION_BASE + 10);
define("YeAPF_SAVE_CACHE_LAST", YeAPF_COLLECTION_BASE + 11);
define("YeAPF_INVALID_CACHE_MODE", YeAPF_COLLECTION_BASE + 12);
define("YeAPF_INVALID_DATA", YeAPF_COLLECTION_BASE + 13);
define("YeAPF_OBSOLETE_FUNCTION", YeAPF_COLLECTION_BASE + 14);
define("YeAPF_EMPTY_POOL", YeAPF_COLLECTION_BASE + 15);
define("YeAPF_ASSETS_FOLDER_NOT_WRITABLE", YeAPF_COLLECTION_BASE + 16);
define("YeAPF_UNKNOWN_DATA_TYPE", YeAPF_COLLECTION_BASE + 17);
define("YeAPF_ASSETS_FOLDER_NOT_READABLE", YeAPF_COLLECTION_BASE + 18);

/***************************/
/* EYESHOT */
define("YeAPF_INNER_JOIN", YeAPF_EYESHOT_BASE + 1);
define("YeAPF_LEFT_JOIN", YeAPF_EYESHOT_BASE + 2);
define("YeAPF_RIGHT_JOIN", YeAPF_EYESHOT_BASE + 3);
define("YeAPF_FULL_JOIN", YeAPF_EYESHOT_BASE + 4);

define("YeAPF_EQUALS", YeAPF_EYESHOT_BASE + 5);
define("YeAPF_NOT_EQUALS", YeAPF_EYESHOT_BASE + 6);
define("YeAPF_GREATER_THAN", YeAPF_EYESHOT_BASE + 7);
define("YeAPF_GREATER_THAN_OR_EQUAL", YeAPF_EYESHOT_BASE + 8);
define("YeAPF_LESS_THAN", YeAPF_EYESHOT_BASE + 9);
define("YeAPF_LESS_THAN_OR_EQUAL", YeAPF_EYESHOT_BASE + 10);
define("YeAPF_IN", YeAPF_EYESHOT_BASE + 11);
define("YeAPF_NOT_IN", YeAPF_EYESHOT_BASE + 12);

define("YeAPF_AND", YeAPF_EYESHOT_BASE + 13);
define("YeAPF_OR", YeAPF_EYESHOT_BASE + 14);
define("YeAPF_NOT", YeAPF_EYESHOT_BASE + 15);

define("YeAPF_MAIN_COLLECTION_ALREADY_DEFINED", YeAPF_EYESHOT_BASE + 16);
define("YeAPF_INVALID_NUMBER_OF_PARAMETERS", YeAPF_EYESHOT_BASE + 17);


/***************************/
/* SERVICE */
define("YeAPF_GET_DESCRIPTION", YeAPF_SERVICE_BASE + 1);
define("YeAPF_GET_OPERATION_ID", YeAPF_SERVICE_BASE + 2);
define("YeAPF_GET_RESPONSES", YeAPF_SERVICE_BASE + 3);
define("YeAPF_GET_CONSTRAINTS", YeAPF_SERVICE_BASE + 4);
define("YeAPF_GET_SECURITY", YeAPF_SERVICE_BASE + 5);
define("YeAPF_GET_PRIVATE_PATH_FLAG", YeAPF_SERVICE_BASE + 6);