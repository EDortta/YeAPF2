<?php declare(strict_types=1);
namespace YeAPF;
/**
 * This file is generated by 'yGenerateBasicTypes.php'
 */

class BasicTypes {
	private static $basicTypes = [];
	public static function startup() {
		self::$basicTypes = [
			'STRING' => [
				'type' => 'STRING',
				'length' => 256,
				'acceptNULL' => '',
				'regExpression' => '/^[^\p{C}]*$/',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'SHORT' => [
				'type' => 'INTEGER',
				'acceptNULL' => '',
				'minValue' => -32767,
				'maxValue' => 32767,
				'regExpression' => '/^([0-9]+$)/',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'UNSIGNEDSHORT' => [
				'type' => 'INTEGER',
				'acceptNULL' => '',
				'minValue' => 0,
				'maxValue' => 65535,
				'regExpression' => '/^([0-9]+$)/',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'LONG' => [
				'type' => 'INTEGER',
				'acceptNULL' => '',
				'minValue' => -2147483647,
				'maxValue' => 2147483647,
				'regExpression' => '/^([0-9]+$)/',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'UNSIGNEDLONG' => [
				'type' => 'INTEGER',
				'acceptNULL' => '',
				'minValue' => 0,
				'maxValue' => 4294967295,
				'regExpression' => '/^([0-9]+$)/',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'FLOAT' => [
				'type' => 'FLOAT',
				'length' => 16,
				'decimals' => 2,
				'acceptNULL' => '',
				'regExpression' => '/^([0-9]+)\.([0-9]+)$/',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'DATE' => [
				'type' => 'DATE',
				'length' => 10,
				'acceptNULL' => '',
				'regExpression' => '/^(([12]\d{3})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))/',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'TIME' => [
				'type' => 'TIME',
				'length' => 8,
				'acceptNULL' => '',
				'regExpression' => '/^([0-2]{1}[0-9]{1}):([0-5]{1}[0-9]{1}):([0-5]{1}[0-9]{1})[Z]{0,}$/',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'DATETIME' => [
				'type' => 'DATETIME',
				'length' => 19,
				'acceptNULL' => '',
				'regExpression' => '/^(([12]\d{3})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))[ T]{1}([0-2]{1}[0-9]{1}):([0-5]{1}[0-9]{1}):([0-5]{1}[0-9]{1})[Z]{0,}$/',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'JSON' => [
				'type' => 'JSON',
				'acceptNULL' => '',
				'regExpression' => '/^[\{\[].*[\}\]]$/',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'BOOLEAN' => [
				'type' => 'BOOLEAN',
				'acceptNULL' => '',
				'regExpression' => '/^(true|false)$/i',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'EMAIL' => [
				'type' => 'STRING',
				'length' => 256,
				'acceptNULL' => '',
				'regExpression' => '/^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'ID' => [
				'type' => 'STRING',
				'length' => 48,
				'acceptNULL' => '',
				'regExpression' => '/^([0-9a-zA-Z_\-\.]+)$/',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'CNPJ' => [
				'type' => 'STRING',
				'length' => 14,
				'acceptNULL' => '',
				'regExpression' => '/^[^\p{C}]*$/',
				'sedInputExpression' => '/[^0-9]//',
				'sedOutputExpression' => '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/$1.$2.$3\/$4-$5/',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'CPF' => [
				'type' => 'STRING',
				'length' => 11,
				'acceptNULL' => '',
				'regExpression' => '/^[^\p{C}]*$/',
				'sedOutputExpression' => '/(\d{3})(\d{3})(\d{3})(\d{2})/$1.$2.$3-$4/',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'URL' => [
				'type' => 'STRING',
				'length' => 2048,
				'acceptNULL' => '',
				'regExpression' => '\b((https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|]|[-A-Z0-9+&@#\/%?=~_|!:,.;]+)\b',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'IPV4' => [
				'type' => 'STRING',
				'length' => 15,
				'acceptNULL' => '',
				'regExpression' => '\b(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'IPV6' => [
				'type' => 'STRING',
				'length' => 45,
				'acceptNULL' => '',
				'regExpression' => '\b([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}\b',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

			'IP' => [
				'type' => 'STRING',
				'length' => 45,
				'acceptNULL' => '',
				'regExpression' => '\b([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}\b|\b(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b',
				'unique' => '',
				'required' => '',
				'primary' => '',
				'tag' => ';;',
			],

		];
	}
	public static function get($keyName) {
		return self::$basicTypes[$keyName]??null;
	}
	public static function list() {
		return array_keys(self::$basicTypes);
	}
	public static function set($keyName, $definition) {
		self::$basicTypes[mb_strtoupper($keyName)] = $definition;
	}
}
BasicTypes::startup();
