<?php declare(strict_types=1);

namespace YeAPF;

/**
 * @author Esteban Dortta
 * @version 2
 * @package YeAPF
 */

/**
 * KeyData is a basic class that keep data associated with an unique key
 *
 * All the plugins classes and objects are inherited from this basic class.
 * That means that all of them has de ability to store some data and be queried
 * by another classes or object.
 */
class KeyData implements \ArrayAccess, \Iterator
{
  private $__data = [];
  private $__position;

  public function __construct()
  {
    $this->__position = 0;
  }

  /**
   * Basic array access.
   * As it implements ArrayAccess, it allow KeyData
   * to be accessed as an associative array
   */

  /**
   * Checks whether the given offset exists in the data array.
   *
   * @param mixed $offset the key to check for existence
   * @return bool true if the offset exists, false otherwise
   */
  public function offsetExists(mixed $offset): bool
  {
    return isset($this->__data[$offset]);
  }

  /**
   * Retrieves the value at the specified offset in the data array.
   *
   * @param mixed $offset The offset to retrieve the value from.
   * @return mixed|null The value at the specified offset or null if it does not exist.
   */
  public function offsetGet(mixed $offset): mixed
  {
    return $this->__get($offset);
  }

  /**
   * Sets the value at the specified offset. It uses the __set()
   * function, so it allow to descendant classes to override it.
   *
   * @param mixed $offset The offset to set the value at.
   * @param mixed $value The value to set.
   * @return void
   */
  public function offsetSet(mixed $offset, mixed $value): void
  {
    $this->__set($offset, $value);
  }

  /**
   * Removes the element at the specified index from the internal data array.
   *
   * @param mixed $offset the key to be unset
   * @return void
   */
  public function offsetUnset(mixed $offset): void
  {
    unset($this->__data[$offset]);
  }

  /**
   * Basic data iterator.
   * As it implements Iterator, it allow KeyData
   * to be iterated by foreach() for example
   */

  /**
   * Retrieves the current element of the array pointer in the object.
   *
   * @return mixed|null The value of the current element or null if it doesn't exist
   */
  public function current(): mixed
  {
    $keys = array_keys($this->__data);
    // return $this->__data[$keys[$this->__position]] ?? null;
    return $this->__get($keys[$this->__position]);
  }

  /**
   * Retrieve the current key of the array.
   *
   * @return mixed
   */
  public function key(): mixed
  {
    $keys = array_keys($this->__data);
    return $keys[$this->__position];
  }

  /**
   * Increments the position of the class iterator.
   *
   * @return void
   */
  public function next(): void
  {
    ++$this->__position;
  }

  /**
   * Rewinds the position of the internal array pointer to 0.
   *
   * @throws None
   * @return void
   */
  public function rewind(): void
  {
    $this->__position = 0;
  }

  /**
   * Checks whether the current position of the iterator is valid.
   *
   * @return bool True if the current position is valid, false otherwise.
   */
  public function valid(): bool
  {
    $keys = array_keys($this->__data);
    return isset($keys[$this->__position]);
  }

  /**
   * Retrieves the data stored in the object and returns it
   * as an associative array
   *
   * @return mixed
   */
  public function exportData(array $exceptionList = [])
  {
    $ret = [];
    foreach ($this->__data as $key => $value) {
      if (!in_array($key, $exceptionList)) {
        $ret[$key] = $this->__get($key);
      }
    }
    return $ret;
  }

  public function exportRawData()
  {
    return $this->__data;
  }

  /**
   * Imports data into the object from an associative array.
   *
   * @param array $data An associative array with key-value pairs to import.
   * @throws Exception If there is an error importing the data.
   * @return void
   */
  public function importData(mixed $data)
  {
    if (!is_array($data) && !is_bool($data)) {
      throw new \YeAPF\YeAPFException('Invalid data type. Only array or bool.', YeAPF_INVALID_DATE_TYPE);
    }
    foreach ($data as $key => $value) {
      $this->__set($key, $value);
    }
  }

  function empty()
  {
    return empty($this->__data);
  }

  /**
   * Basic data setter. This is the function called when
   * an object property is setted as in $obj->aux=100
   *
   * @param string $name
   * @param mixed $value
   *
   * @return void
   */
  public function __set(string $name, mixed $value)
  {
    $this->__data[$name] = $value;
  }

  /**
   * Basic data getter. This is the function called when
   * an object property is retrieved as in $num = $obj->aux
   *
   * @param mixed $name
   *
   * @return void
   */
  public function __get(string $name)
  {
    return $this->__data[$name] ?? null;
  }

  /**
   * Checks if a property is set in the object.
   *
   * @param string $name The name of the property to check.
   * @return bool Returns true if the property is set, false otherwise.
   */
  public function __isset($name)
  {
    return isset($this->__data[$name]);
  }

  /**
   * Unsets the value of a specified property in the object's data array.
   * This is the function called when an object property is unsetted
   * as in unset($obj->aux)
   *
   * @param string $name The name of the property to unset
   * @throws null
   * @return void
   */
  public function __unset($name)
  {
    unset($this->__data[$name]);
  }

  /**
   * Deletes a key-value pair from the internal data array.
   *
   * @param mixed $name the key to delete
   * @throws Exception if the key does not exist
   */
  public function delete($name)
  {
    $this->__unset($name);
  }

  /**
   * Clears the data of the object.
   */
  public function clear()
  {
    $this->__data = [];
  }

  /**
   * Retrieves all the keys of the object's data array.
   *
   * @return array The keys of the data array.
   */
  public function keys()
  {
    return array_keys($this->__data);
  }
}

/**
 * This is a PHP class named SanitizedKeyData that extends the KeyData class.
 * It provides methods for setting and retrieving constraints for keys.
 * It also provides a method for checking constraints and sanitizing input
 * values before setting the value of a specified property.
 * As it is putted just before the value of a key is setted, the programmed
 * does not need to use the methods directly.
 *
 * @example treco.php
 */
class SanitizedKeyData extends KeyData
{
  private $__constraints = [];

  public function __construct(array $constraints = null)
  {
    parent::__construct();
    if (null !== $constraints) {
      $this->importConstraints($constraints);
    }
  }

  /**
   * Sets a constraint for a given key in the YeAPF library.
   *
   * @param string $keyName The name of the key to set a constraint for.
   * @param string $keyType The data type of the key.
   * @param bool $acceptNULL Whether the key can accept a NULL value.
   * @param int|null $length The length of the key.
   * @param int|null $decimals The number of decimal places for the key.
   * @param float|null $minValue The minimum value for the key.
   * @param float|null $maxValue The maximum value for the key.
   * @param string|null $regExpression The regular expression for the data be accepted in the key.
   * @param string|null $sedInputExpression The sed input expression to be used to save the data in the key.
   * @param string|null $sedOutputExpression The sed output expression to be used to format the data from the key.
   * @param bool $unique Whether the key is unique.
   * @param bool $required Whether the key is required.
   * @param bool $primary Whether the key is a primary key.
   * @param int|null $protobufOrder The order of the key in the protobuf.
   * @param string|null $tag The tag of the key. Can be splitted using ';'
   * @param any|null $defaultValue The default value of the key.
   * @throws \YeAPF\YeAPFException If an invalid key type is provided or if the key already exists.
   * @return void
   */
  public function setConstraint(
    string $keyName,
    string $keyType,
    bool $acceptNULL = false,
    int $length = null,
    int $decimals = null,
    float $minValue = null,
    float $maxValue = null,
    string $regExpression = null,
    string $sedInputExpression = null,
    string $sedOutputExpression = null,
    bool $unique = false,
    bool $required = false,
    bool $primary = false,
    int $protobufOrder = null,
    string $tag = null,
    $defaultValue = null
  ) {
    $validTypes = [YeAPF_TYPE_STRING, YeAPF_TYPE_INT, YeAPF_TYPE_FLOAT, YeAPF_TYPE_BOOL, YeAPF_TYPE_DATE, YeAPF_TYPE_TIME, YeAPF_TYPE_DATETIME, YeAPF_TYPE_BYTES, YeAPF_TYPE_JSON];
    if (!in_array($keyType, $validTypes)) {
      throw new \YeAPF\YeAPFException('Invalid key type', YeAPF_INVALID_KEY_TYPE);
    } else {
      if (self::__isset($keyName)) {
        throw new \YeAPF\YeAPFException('Key already exists', YeAPF_INVALID_KEY_VALUE);
      }
      if (null == $keyType) {
        unset($this->__constraints[$keyName]);
      } else {
        switch ($keyType) {
          case YeAPF_TYPE_STRING:
            if (null == $regExpression) {
              // $regExpression = '/[0-9a-zA-Z_.,\-\+\*\/\@\#\!$\%\^\&\[\]\(\){}]*/';
              $regExpression = YeAPF_STRING_REGEX;
            }

            break;

          case YeAPF_TYPE_INT:
            $regExpression = YeAPF_INT_REGEX;
            break;

          case YeAPF_TYPE_FLOAT:
            $regExpression = YeAPF_FLOAT_REGEX;
            break;

          case YeAPF_TYPE_BOOL:
            $regExpression = YeAPF_BOOL_REGEX;
            break;

          case YeAPF_TYPE_DATE:
            $regExpression = YeAPF_DATE_REGEX;
            break;

          case YeAPF_TYPE_TIME:
            $regExpression = YeAPF_TIME_REGEX;
            break;

          case YeAPF_TYPE_DATETIME:
            $regExpression = YeAPF_DATETIME_REGEX;
            break;

          case YeAPF_TYPE_BYTES:
            $regExpression = '(.*)';
            break;

          case YeAPF_TYPE_JSON:
            $regExpression = '/^[\{\[].*[\}\]]$/';
            break;

          default:
            throw new \YeAPF\YeAPFException("Not implemented key type [ $keyType ]", YeAPF_UNIMPLEMENTED_KEY_TYPE);
        }
        if (YeAPF_TYPE_INT == $keyType && null != $minValue) {
          $minValue = intval($minValue);
          $decimals = null;
        }
        if (YeAPF_TYPE_INT == $keyType && null != $maxValue) {
          $maxValue = intval($maxValue);
          $decimals = null;
        }

        if (YeAPF_TYPE_BOOL == $keyType) {
          $minValue = null;
          $maxValue = null;
          $decimals = null;
          $length = null;
        }

        if (YeAPF_TYPE_DATE == $keyType) {
          $minValue = null;
          $maxValue = null;
          $decimals = null;
          $length = 10;
        }

        if (YeAPF_TYPE_TIME == $keyType) {
          $minValue = null;
          $maxValue = null;
          $decimals = null;
          $length = 8;
        }

        if (YeAPF_TYPE_DATETIME == $keyType) {
          $minValue = null;
          $maxValue = null;
          $decimals = null;
          $length = 19;
        }

        if (YeAPF_TYPE_FLOAT == $keyType) {
          if (null != $decimals) {
            if (null == $length) {
              throw new \YeAPF\YeAPFException("Length is required when using decimals in $keyName", YeAPF_LENGTH_IS_REQUIRED);
            }
          }
        }

        if (null !== $protobufOrder) {
          if (0 == $protobufOrder) {
            foreach ($this->__constraints as $key => $value) {
              if ($value['protobufOrder'] > $protobufOrder) {
                $protobufOrder = $value['protobufOrder'];
              }
            }
            $protobufOrder++;
            $protobufOrder = max(1, $protobufOrder);
          } else {
            foreach ($this->__constraints as $key => $value) {
              if ($protobufOrder == $value['protobufOrder']) {
                new \YeAPF\YeAPFException("Protobuf order is not unique in '$keyName'. '$key' is using the same value $protobufOrder", YeAPF_PROTOBUF_ORDER_IS_NOT_UNIQUE);
              }
            }
          }
        }

        if (null == $tag)
          $tag = '*';

        $tag = preg_replace('/[^a-zA-Z_0-9;]/', '', $tag);

        $this->__constraints[$keyName] = [
          'type' => $keyType,
          'length' => $length,
          'decimals' => $decimals,
          'acceptNULL' => $acceptNULL,
          'minValue' => $minValue,
          'maxValue' => $maxValue,
          'regExpression' => $regExpression,
          'sedInputExpression' => $sedInputExpression,
          'sedOutputExpression' => $sedOutputExpression,
          'unique' => $unique,
          'required' => $required,
          'primary' => $primary,
          'protobufOrder' => $protobufOrder,
          'tag' => ';' . join(';', explode(';', $tag)) . ';',
          'defaultValue' => $defaultValue
        ];
      }
    }
  }

  public function setConstraintFromJSON(string $keyName, $json)
  {
    if (is_string($json)) {
      $json = json_decode($json, true);
    }

    if (!is_array($json)) {
      throw new \InvalidArgumentException("Invalid JSON data for key '$keyName'");
    }

    $constraint[$keyName] = $json;

    $this->importConstraints($constraint);
  }

  /**
   * Retrieve the constraint associated with the given key name.
   *
   * @param string $keyName The name of the key to retrieve the constraint for.
   * @return mixed The constraint associated with the given key name.
   */
  public function getConstraint(string $keyName)
  {
    return $this->__constraints[$keyName];
  }

  public function getConstraintAsJSON($keyName)
  {
    return json_encode($this->getConstraint($keyName));
  }

  /**
   * Retrieves the constraints associated with this object.
   *
   * @return array The constraints associated with this object.
   */
  public function getConstraints(bool $asInterface = null, string $tag = null)
  {
    $debug = false;
    if ($debug)
      _log('getConstraints() from ' . print_r($this->__constraints, true));
    if (is_null($asInterface) && is_null($tag)) {
      return $this->__constraints;
    } else {
      $ret = [];
      if (null != $tag) {
        $tag = ';' . preg_replace('/[^a-zA-Z_0-9]/', '', $tag) . ';';
      }
      foreach ($this->__constraints as $key => $value) {
        if (null == $tag || strpos($value['tag'], $tag) !== false || $value['tag'] == ';*;') {
          if ($asInterface) {
            if ($value['protobufOrder'] > 0) {
              $ret[$key] = $value;
            }
          } else {
            $ret[$key] = $value;
          }
        }
      }
      return $ret;
    }
  }

  public function importConstraints(array $constraints, bool $cleanup = false)
  {
    if ($cleanup)
      $this->__constraints = [];
    foreach ($constraints as $keyName => $constraint) {
      $this->__constraints[$keyName] = [
        'type' => $constraint['type'],
        'length' => $constraint['length'] ?? null,
        'decimals' => $constraint['decimals'] ?? null,
        'acceptNULL' => $constraint['acceptNULL'] ?? null,
        'minValue' => $constraint['minValue'] ?? null,
        'maxValue' => $constraint['maxValue'] ?? null,
        'regExpression' => $constraint['regExpression'] ?? null,
        'sedInputExpression' => $constraint['sedInputExpression'] ?? null,
        'sedOutputExpression' => $constraint['sedOutputExpression'] ?? null,
        'unique' => $constraint['unique'] ?? false,
        'required' => $constraint['required'] ?? false,
        'primary' => $constraint['primary'] ?? false,
        'tag' => $constraint['tag'] ?? null
      ];
      if (!empty($constraint['protobufOrder'])) {
        $this->__constraints[$keyName]['protobufOrder'] = $constraint['protobufOrder'];
      }
    }
  }

  /**
   * Validates a given constraint for a key-value pair. This was intended
   * to be used internally by the class. But can be used as a form of avoid
   * invalid variables values.
   *
   * @param string $keyName The name of the key whose constraint is to be checked
   * @param mixed $value The value to be validated against the constraint
   *
   * @throws \YeAPF\YeAPFException When the value violates the constraint
   *
   * @return mixed The validated value if it satisfies the constraint
   */
  public function checkConstraint(string $keyName, mixed $value)
  {
    $debug = true;
    if ($debug)
      _log('Checkpoint#0');
    try {
      // if ($debug) {
      //   _log('  constraints: '.print_r($this->__constraints, true));
      // }
      if (isset($this->__constraints[$keyName])) {
        $type = $this->__constraints[$keyName]['type'];
        $length = $this->__constraints[$keyName]['length'] ?? 0;
        $decimals = $this->__constraints[$keyName]['decimals'] ?? 0;
        $acceptNULL = $this->__constraints[$keyName]['acceptNULL'] ?? false;
        $minValue = $this->__constraints[$keyName]['minValue'] ?? null;
        $maxValue = $this->__constraints[$keyName]['maxValue'] ?? null;
        $regExpression = $this->__constraints[$keyName]['regExpression'] ?? null;
        $defaultValue = $this->__constraints[$keyName]['defaultValue'] ?? null;

        if (!$acceptNULL && (empty($value) || $value === null)) {
          $value = $defaultValue ?? null;
        }

        if ($debug)
          _log('Checkpoint#1');
        if (null === $value && !$acceptNULL) {
          if ($debug)
            _log('Checkpoint#2 at ' . __LINE__);
          list($message, $error) = ['null not allowed in ' . get_class() . ' -> ' . $keyName, YeAPF_NULL_NOT_ALLOWED];
          if ($debug)
            _log($message);
          throw new \YeAPF\YeAPFException($message, YeAPF_NULL_NOT_ALLOWED);
        } else {
          if (YeAPF_TYPE_STRING == $type) {
            if ($debug)
              _log('Checkpoint#2 at ' . __LINE__);
            $pureValue=$this->unsanitize($value??'');
            if ($length > 0 && strlen($pureValue ?? '') > $length) {
              list($message, $error) = ['String value has ' . strlen($pureValue ?? '') . ' chars. Value too long in ' . get_class() . '.' . $keyName . ' It is ' . $length . ' chars long', YeAPF_INVALID_KEY_VALUE];
              if ($debug)
                _log($message);
              throw new \YeAPF\YeAPFException($message, YeAPF_VALUE_TOO_LONG);
            }
          } elseif (YeAPF_TYPE_INT == $type) {
            if ($debug)
              _log('Checkpoint#2 at ' . __LINE__);
            if ($value == '' && $acceptNULL) {
              $value = null;
            }
            if (!(null === $value && $acceptNULL)) {
              if (is_string($value)) {
                if (is_numeric($value)) {
                  if ((int) $value == $value)
                    $value = (int) $value;
                }
              }
              if (!is_int($value)) {
                list($message, $error) = ['INVALID INTEGER value in ' . get_class() . '.' . $keyName, YeAPF_INVALID_KEY_VALUE];
                if ($debug)
                  _log($message);
                throw new \YeAPF\YeAPFException($message, YeAPF_INVALID_INT_VALUE);
              }
              if (null !== $minValue) {
                if ($value < $minValue) {
                  list($message, $error) = ['INVALID INTEGER value: out of bounds in ' . get_class() . '.' . $keyName, YeAPF_VALUE_OUT_OF_RANGE];
                  if ($debug)
                    _log($message);
                  throw new \YeAPF\YeAPFException($message, YeAPF_INVALID_INT_VALUE);
                }
              }

              if (null !== $maxValue) {
                if ($value > $maxValue) {
                  list($message, $error) = ['INVALID INTEGER value: out of bounds in ' . get_class() . '.' . $keyName, YeAPF_VALUE_OUT_OF_RANGE];
                  if ($debug)
                    _log($message);
                  throw new \YeAPF\YeAPFException($message, YeAPF_INVALID_INT_VALUE);
                }
              }
            }
          } elseif (YeAPF_TYPE_FLOAT == $type) {
            if ($debug)
              _log('Checkpoint#2 at ' . __LINE__);
            if (!(null === $value && $acceptNULL)) {
              if (!is_numeric($value)) {
                list($message, $error) = ['INVALID FLOAT value in ' . get_class() . '.' . $keyName, YeAPF_INVALID_KEY_VALUE];
                if ($debug)
                  _log($message);
                throw new \YeAPF\YeAPFException($message, YeAPF_INVALID_FLOAT_VALUE);
              }
              if (null !== $minValue) {
                if ($value < $minValue) {
                  list($message, $error) = ['INVALID FLOAT value: out of bounds in ' . get_class() . '.' . $keyName, YeAPF_VALUE_OUT_OF_RANGE];
                  if ($debug)
                    _log($message);
                  throw new \YeAPF\YeAPFException($message, YeAPF_INVALID_FLOAT_VALUE);
                }
              }
              if (null !== $maxValue) {
                if ($value > $maxValue) {
                  list($message, $error) = ['INVALID FLOAT value: out of bounds in ' . get_class() . '.' . $keyName, YeAPF_VALUE_OUT_OF_RANGE];
                  if ($debug)
                    _log($message);
                  throw new \YeAPF\YeAPFException($message, YeAPF_INVALID_FLOAT_VALUE);
                }
              }
            }
          } elseif (YeAPF_TYPE_BOOL == $type) {
            if ($debug)
              _log('Checkpoint#2 at ' . __LINE__);
            if (!(null === $value && $acceptNULL)) {
              if (!is_bool($value)) {
                if (1 == $value || 'Y' == strtoupper("$value")) {
                  $value = true;
                }

                if (0 == $value || 'N' == strtoupper("$value")) {
                  $value = false;
                }
              }
              $validTrueStrings = ['true', 'yes', '1', 'y', 't', 'enabled', 'on'];
              $validFalseStrings = ['false', 'no', '0', 'n', 'f', 'disabled', 'off'];
              if (is_string($value)) {
                if (in_array(mb_strtolower(trim($value)), $validTrueStrings)) {
                  $value = true;
                } elseif (in_array(mb_strtolower(trim($value)), $validFalseStrings)) {
                  $value = false;
                }
              }
              if (!is_bool($value)) {
                list($message, $error) = ['INVALID BOOLEAN value in ' . get_class() . '.' . $keyName . ': ' . print_r($value, true), YeAPF_INVALID_KEY_VALUE];
                if ($debug)
                  _log($message);
                throw new \YeAPF\YeAPFException($message, YeAPF_VALUE_OUT_OF_RANGE);
              }
            }
          } elseif (YeAPF_TYPE_DATE == $type) {
            if ($debug)
              _log('Checkpoint#2 at ' . __LINE__);
            if (!(null === $value && $acceptNULL)) {
              if (!is_string($value)) {
                list($message, $error) = ['INVALID DATE type (string expected) in ' . get_class() . '.' . $keyName, YeAPF_INVALID_DATE_TYPE];
                if ($debug)
                  _log($message);
                throw new \YeAPF\YeAPFException($message, YeAPF_INVALID_DATE_TYPE);
              } else {
                if (!preg_match(YeAPF_DATE_REGEX, $value)) {
                  list($message, $error) = ['INVALID DATE value in ' . get_class() . '.' . $keyName, YeAPF_INVALID_DATE_VALUE];
                  if ($debug)
                    _log($message);
                  {
                    throw new \YeAPF\YeAPFException($message, YeAPF_INVALID_DATE_VALUE);
                  }
                }
                if (strlen($value) > $length) {
                  list($message, $error) = ['INVALID DATE. Value too long in ' . get_class() . '.' . $keyName . ' (' . strlen($value) . ' presents but no more than ' . $length . ' chars allowed)', YeAPF_VALUE_TOO_LONG];
                  if ($debug)
                    _log($message);
                  throw new \YeAPF\YeAPFException($message, YeAPF_VALUE_TOO_LONG);
                }
              }
            }
          } elseif (YeAPF_TYPE_DATETIME == $type) {
            if ($debug)
              _log('Checkpoint#2 at ' . __LINE__);
            if (!(null === $value && $acceptNULL)) {
              if (!is_string($value)) {
                list($message, $error) = ['INVALID DATETIME type (string expected) in ' . get_class() . '.' . $keyName, YeAPF_INVALID_DATE_TYPE];
                if ($debug)
                  _log($message);
                throw new \YeAPF\YeAPFException($message, YeAPF_INVALID_DATETIME_VALUE);
              } else {
                if ($value > '' && strlen($value) < $length) {
                  if (preg_match(YeAPF_DATE_REGEX, $value)) {
                    $value .= ' 00:00:00';
                  }
                }
                if (!preg_match(YeAPF_DATETIME_REGEX, $value)) {
                  list($message, $error) = ['INVALID DATETIME value in ' . get_class() . '.' . $keyName, YeAPF_INVALID_DATE_VALUE];
                  if ($debug)
                    _log($message);
                  throw new \YeAPF\YeAPFException($message, YeAPF_INVALID_DATETIME_VALUE);
                }
              }
            }
          } elseif (YeAPF_TYPE_TIME == $type) {
            if ($debug)
              _log('Checkpoint#2 at ' . __LINE__);
            if (!(null === $value && $acceptNULL)) {
              if (!is_string($value)) {
                list($message, $error) = ['INVALID TIME type (string expected) in ' . get_class() . '.' . $keyName, YeAPF_INVALID_DATE_TYPE];
                if ($debug)
                  _log($message);
                throw new \YeAPF\YeAPFException($message, YeAPF_INVALID_TIME_TYPE);
              } else {
                if (!preg_match(YeAPF_TIME_REGEX, $value))
                  list($message, $error) = ['INVALID TIME value in ' . get_class() . '.' . $keyName, YeAPF_INVALID_DATE_VALUE];
                if ($debug)
                  _log($message);
                {
                  throw new \YeAPF\YeAPFException($message, YeAPF_INVALID_TIME_VALUE);
                }
              }
            }
          } elseif (YeAPF_TYPE_JSON == $type) {
            if ($debug)
              _log('Checkpoint#2 at ' . __LINE__);
            if (!(null === $value && $acceptNULL)) {
              if (is_array($value)) {
                $value = json_encode($value);
              }
              if (!is_string($value)) {
                list($message, $error) = ['INVALID JSON type (string expected) in ' . get_class() . '.' . $keyName, YeAPF_INVALID_JSON_TYPE];
                if ($debug)
                  _log($message);
                throw new \YeAPF\YeAPFException($message, YeAPF_INVALID_JSON_TYPE);
              } else {
                $json = json_decode($value, true);
                if (null === $json) {
                  list($message, $error) = ['INVALID JSON value in ' . get_class() . '.' . $keyName, YeAPF_INVALID_JSON_VALUE];
                  if ($debug)
                    _log($message);
                  throw new \YeAPF\YeAPFException($message, YeAPF_INVALID_JSON_VALUE);
                }
              }
            }
          } else {
            if ($debug)
              _log('Checkpoint#2 at ' . __LINE__);
            list($message, $error) = ['INVALID KEY type in ' . get_class() . '.' . $keyName . ' or not implemented', YeAPF_INVALID_KEY_TYPE];
            if ($debug)
              _log($message);
            throw new \YeAPF\YeAPFException($message, YeAPF_INVALID_KEY_TYPE);
          }
        }

        if ($debug)
          _log('Checkpoint#3');

        if (null !== $regExpression) {
          if ($value !== null) {
            if ($debug)
              _log('RegExpression: ' . $regExpression . ' in ' . get_class() . ' -> ' . $keyName);
            if (false === preg_match($regExpression, "$value")) {
              list($message, $error) = ["Value does not satisfies '$regExpression' in " . get_class() . ' -> ' . $keyName, YeAPF_INVALID_KEY_VALUE];
              if ($debug)
                _log($message);
              throw new \YeAPF\YeAPFException($message, YeAPF_VALUE_DOES_NOT_SATISFY_REGEXP);
            } else if ($debug)
              _log('Value satisfies the pattern.');
          }
        }
        if ($debug)
          _log('Checkpoint#3');
      } else {
        if ($debug)
          _log("WARNING: no constraint for $keyName");
      }
    } catch (\Throwable $th) {
      list($message, $error) = [$th->getMessage(), $th->getCode()];
      if ($debug) {
        _log($message);
      }
      throw new \YeAPF\YeAPFException($message, $error);
    }
    if ($debug)
      _log('Accepting value ' . print_r($value, true));
    return $value;
  }

  /**
   * Sanitizes a value.
   * The first intention of this method is to avoid unwanted characters
   * in the database. Meanwhile, as it can be used outside of that scope,
   * the string values just are converted to html.
   * The length is not to be checked here.
   *
   * @param mixed $value the value to sanitize
   * @return mixed the sanitized value
   */
  private function sanitize($value)
  {
    if (is_array($value)) {
      $result = [];
      foreach ($value as $item) {
        $result[] = $this->sanitize($item);
      }
      return $result;
    }
    if (is_string($value)) {
      //   \_log("    sanitizing $value  -> ");
      $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
      //   \_log("$value\n");
    } else {
      $value = $value;
    }

    return $value;
  }

  private function unsanitize($value)
  {
    if (is_array($value)) {
      $result = [];
      foreach ($value as $item) {
        $result[] = $this->unsanitize($item);
      }
      return $result;
    }
    if (is_string($value)) {
      //   \_log(" ( unsanitize $value  -> ");
      while (preg_match('/&(#\d+|[a-zA-Z]+);/', $value)) {
        $value = htmlspecialchars_decode($value, ENT_QUOTES);
        // \_log(" [$value]  ");
      }
      //   \_log(") ");
    } else {
      $value = $value;
    }

    return $value;
  }

  /**
   * Sets the value of the specified property after sanitizing it and checking
   * constraints.
   *
   * @param string $name the name of the property to set
   * @param mixed $value the value to set the property to
   * @throws YeAPF\YeAPFException if an error occurs during constraint checking
   */
  public function __set(string $name, mixed $value)
  {
    $debug = true;
    
    $value = $this->sanitize($value);
    if ($debug)
      _log("Setting '$name' with ---[" . print_r($value, true) . "]---\n");

    $regExpCount = 0;
    if (\YeAPF\YeAPFConfig::allowExpressionsInSanitizedInput()) {
      if (is_string($value)) {
        $auxValue = $value;
        $simplifiedExpression = [];
        $regExpOpened = false;
        while (preg_match('/(%(GTE|GT|EQ|NEQ|LT|LTE|BT|GREATERTHANOREQUALSTO|GREATERTHAN|EQUALSTO|NOTEQUALSTO|LESSTHAN|LESSTHANOREQUALTOO|BETWEEN))\(([\'|"]{0,}[0-9\. \t]{0,}[\'|"]{0,})\)/i', $auxValue, $reg_parts)) {
          $regExpOpened = false;
          $regExpCount++;
          $pos = strpos($auxValue, $reg_parts[0]);
          $simplifiedExpression[] = substr($auxValue, $pos, strlen($reg_parts[0]));
          $auxValue = trim(substr($auxValue, $pos + strlen($reg_parts[0])));
          if (strlen($auxValue) > 0) {
            if (preg_match('/(and|or)/i', $auxValue, $reg_parts_2)) {
              $simplifiedExpression[] = $reg_parts_2[0];
              $auxValue = substr($auxValue, strlen($reg_parts_2[0]));
              $regExpOpened = true;
            } else {
              throw new YeAPF\YeAPFException('Invalid expression: logical operator expected', YeAPF_EXPRESSION_NOT_VALID);
            }
          }
        }

        if ($regExpOpened) {
          throw new YeAPF\YeAPFException('Incomplete expression', YeAPF_EXPRESSION_NOT_VALID);
        }
      }
    }
    if (0 == $regExpCount) {
      // print_r('['.$name.'] ');
      // print_r($value);
      $value = $this->checkConstraint($name, $value);
      if ($debug)
        _log('  :: value = ' . print_r($value, true) . "\n");
      if (null !== $value) {
        $sedInputExpression = $this->__constraints[$name]['sedInputExpression'] ?? null;
        if (null !== $sedInputExpression) {
          if ($debug)
            _log("Applying sedInputExpression $sedInputExpression\n");
          $expression = trim($sedInputExpression, "'");
          $expression = str_replace('\/', '#', $expression);
          list($pattern, $replacement) = preg_split('/\//', $expression, -1, PREG_SPLIT_NO_EMPTY);
          $pattern = str_replace('#', '\/', $pattern);
          $replacement = str_replace('#', '/', $replacement);
          $max_length = strlen($value);
          preg_match_all('/{(\d+)}/', $pattern, $matches);
          if (isset($matches[1])) {
            $max_length = 0;
            foreach ($matches[1] as $field_len) {
              $max_length += $field_len;
            }
          }
          $value2 = substr($value, 0, $max_length);

          $value = preg_replace("/$pattern/", $replacement, $value2);
        }
      }
    } else {
      $value = $simplifiedExpression;
    }
    parent::__set($name, $value);
  }

  public function __get(string $name)
  {
    $debug = false;
    if ($debug)
      _log("  :: getting '$name' -> ");
    $value = parent::__get($name);
    if ($debug)
      _log(print_r($value, true) . ' -> ');
    $value = $this->unsanitize($value);
    if ($debug)
      _log(print_r($value, true) . "\n");

    $sedOutputExpression = $this->__constraints[$name]['sedOutputExpression'] ?? null;
    if (null !== $sedOutputExpression) {
      if ($debug)
        _log("Applying sedOutputExpression $sedOutputExpression\n");
      $expression = trim($sedOutputExpression, "'");
      $expression = str_replace('\/', '#', $expression);
      list($pattern, $replacement) = preg_split('/\//', $expression, -1, PREG_SPLIT_NO_EMPTY);
      $pattern = str_replace('#', '\/', $pattern);
      $replacement = str_replace('#', '/', $replacement);
      $value2 = $value;

      if ($debug)
        _log("$pattern -> $replacement on '$value'\n");
      $value = preg_replace("/$pattern/", $replacement, $value2);
    }

    return $value;
  }

  public function __get_raw_value(string $name)
  {
    // \_log("  :: getting raw value '$name' -> ");
    $value = parent::__get($name);
    // \_log("$value\n");
    return $value;
  }
}

function translateObject($source, $destinationClassName)
{
  $sourceReflection = new \ReflectionObject($source);
  $destinationReflection = new \ReflectionClass($destinationClassName);

  $destination = $destinationReflection->newInstanceWithoutConstructor();

  foreach ($sourceReflection->getProperties() as $property) {
    $propertyName = $property->getName();

    if ($destinationReflection->hasProperty($propertyName)) {
      $destinationProperty = $destinationReflection->getProperty($propertyName);
      $property->setAccessible(true);
      $destinationProperty->setAccessible(true);
      $destinationProperty->setValue($destination, $property->getValue($source));
    }
  }

  return $destination;
}
