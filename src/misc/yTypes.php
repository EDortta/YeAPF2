<?php declare(strict_types=1);
namespace YeAPF;
class BasicTypes {
	private static $basicTypes = [];
	public static function startup() {
		self::$basicTypes = [
			'string' => [
				'type' => 'string',
				'length' => 256,
				'regExpression' => '/^[^\p{C}]*$/u',
				'tag' => ';;',
			],

			'short' => [
				'type' => 'integer',
				'minValue' => -32767,
				'maxValue' => 32767,
				'regExpression' => '/^([0-9]+$)/',
				'tag' => ';;',
			],

			'unsignedShort' => [
				'type' => 'integer',
				'maxValue' => 65535,
				'regExpression' => '/^([0-9]+$)/',
				'tag' => ';;',
			],

			'long' => [
				'type' => 'integer',
				'minValue' => -2147483647,
				'maxValue' => 2147483647,
				'regExpression' => '/^([0-9]+$)/',
				'tag' => ';;',
			],

			'unsignedLong' => [
				'type' => 'integer',
				'maxValue' => 4294967295,
				'regExpression' => '/^([0-9]+$)/',
				'tag' => ';;',
			],

			'float' => [
				'type' => 'float',
				'length' => 16,
				'decimals' => 2,
				'regExpression' => '/^([0-9]+)\.([0-9]+)$/',
				'tag' => ';;',
			],

			'date' => [
				'type' => 'date',
				'length' => 10,
				'regExpression' => '/^(([12]\d{3})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))/',
				'tag' => ';;',
			],

			'time' => [
				'type' => 'time',
				'length' => 8,
				'regExpression' => '/^([0-2]{1}[0-9]{1}):([0-5]{1}[0-9]{1}):([0-5]{1}[0-9]{1})[Z]{0,}$/',
				'tag' => ';;',
			],

			'datetime' => [
				'type' => 'datetime',
				'length' => 19,
				'regExpression' => '/^(([12]\d{3})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))[ T]{1}([0-2]{1}[0-9]{1}):([0-5]{1}[0-9]{1}):([0-5]{1}[0-9]{1})[Z]{0,}$/',
				'tag' => ';;',
			],

			'json' => [
				'type' => 'json',
				'regExpression' => '/^[\{\[].*[\}\]]$/',
				'tag' => ';;',
			],

			'bool' => [
				'type' => 'boolean',
				'regExpression' => '/^(true|false)$/i',
				'tag' => ';;',
			],

			'email' => [
				'type' => 'string',
				'length' => 256,
				'regExpression' => '/^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/',
				'tag' => ';;',
			],

			'cnpj' => [
				'type' => 'string',
				'length' => 14,
				'regExpression' => '/^[^\p{C}]*$/u',
				'sedOutputExpression' => '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/$1.$2.$3\/$4-$5/',
				'tag' => ';;',
			],

			'cpf' => [
				'type' => 'string',
				'length' => 11,
				'regExpression' => '/^[^\p{C}]*$/u',
				'sedOutputExpression' => '/(\d{3})(\d{3})(\d{3})(\d{2})/$1.$2.$3-$4/',
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
		self::$basicTypes[$keyName] = $definition;
	}
}
BasicTypes::startup();