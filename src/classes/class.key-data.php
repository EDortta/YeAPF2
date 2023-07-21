<?php
declare (strict_types = 1);
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

  public function __construct() {
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
  public function offsetExists(mixed $offset): bool {
    return isset($this->__data[$offset]);
  }

  /**
   * Retrieves the value at the specified offset in the data array.
   *
   * @param mixed $offset The offset to retrieve the value from.
   * @return mixed|null The value at the specified offset or null if it does not exist.
   */
  public function offsetGet(mixed $offset): mixed {
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
  public function offsetSet(mixed $offset, mixed $value): void {
    $this->__set($offset, $value);
  }

  /**
   * Removes the element at the specified index from the internal data array.
   *
   * @param mixed $offset the key to be unset
   * @return void
   */
  public function offsetUnset(mixed $offset): void {
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
  public function current(): mixed {
    $keys = array_keys($this->__data);
    // return $this->__data[$keys[$this->__position]] ?? null;
    return $this->__get($keys[$this->__position]);
  }

  /**
   * Retrieve the current key of the array.
   *
   * @return mixed
   */
  public function key(): mixed {
    $keys = array_keys($this->__data);
    return $keys[$this->__position];
  }

  /**
   * Increments the position of the class iterator.
   *
   * @return void
   */
  public function next(): void {
    ++$this->__position;
  }

  /**
   * Rewinds the position of the internal array pointer to 0.
   *
   * @throws None
   * @return void
   */
  public function rewind(): void {
    $this->__position = 0;
  }

  /**
   * Checks whether the current position of the iterator is valid.
   *
   * @return bool True if the current position is valid, false otherwise.
   */
  public function valid(): bool {
    $keys = array_keys($this->__data);
    return isset($keys[$this->__position]);
  }

  /**
   * Retrieves the data stored in the object and returns it
   * as an associative array
   *
   * @return mixed
   */
  public function exportData() {
    $ret = [];
    foreach ($this->__data as $key => $value) {
      $ret[$key] = $this->__get($key);
    }
    return $ret;
  }

  public function exportRawData() {
    return $this->__data;
  }

  /**
   * Imports data into the object from an associative array.
   *
   * @param array $data An associative array with key-value pairs to import.
   * @throws Exception If there is an error importing the data.
   * @return void
   */
  public function importData(mixed $data) {
    if (!is_array($data) && !is_bool($data)) {
      throw new \YeAPF\YeAPFException('Invalid data type. Only array or bool.');
    }
    foreach ($data as $key => $value) {
      $this->__set($key, $value);
    }
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
  public function __set(string $name, mixed $value) {
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
  public function __get(string $name) {
    return $this->__data[$name] ?? null;
  }

  /**
   * Checks if a property is set in the object.
   *
   * @param string $name The name of the property to check.
   * @return bool Returns true if the property is set, false otherwise.
   */
  public function __isset($name) {
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
  public function __unset($name) {
    unset($this->__data[$name]);
  }

  /**
   * Deletes a key-value pair from the internal data array.
   *
   * @param mixed $name the key to delete
   * @throws Exception if the key does not exist
   */
  public function delete($name) {
    $this->__unset($name);
  }

  /**
   * Clears the data of the object.
   */
  public function clear() {
    $this->__data = [];
  }

  /**
   * Retrieves all the keys of the object's data array.
   *
   * @return array The keys of the data array.
   */
  public function keys() {
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
class SanitizedKeyData extends KeyData {
  private $__constraints = [];

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
   * @param string|null $regExpression The regular expression for the key.
   * @param bool $unique Whether the key is unique.
   * @param bool $required Whether the key is required.
   * @param bool $primary Whether the key is a primary key.
   * @param int|null $protobufOrder The order of the key in the protobuf.
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
    bool $unique = false,
    bool $required = false,
    bool $primary = false,
    int $protobufOrder = null) {
    $validTypes = [YeAPF_TYPE_STRING, YeAPF_TYPE_INT, YeAPF_TYPE_FLOAT, YeAPF_TYPE_BOOL, YeAPF_TYPE_DATE, YeAPF_TYPE_TIME, YeAPF_TYPE_DATETIME, YeAPF_TYPE_BYTES];
    if (!in_array($keyType, $validTypes)) {
      throw new \YeAPF\YeAPFException("Invalid key type", YeAPF_INVALID_KEY_TYPE);
    } else {
      if (self::__isset($keyName)) {
        throw new \YeAPF\YeAPFException("Key already exists", YeAPF_INVALID_KEY_VALUE);
      }
      if (null == $keyType) {
        unset($this->__constraints[$keyName]);
      } else {
        switch ($keyType) {
        case YeAPF_TYPE_STRING:
          if (null == $regExpression) {
            $regExpression = "[0-9a-zA-Z_.,\-\+\*\/\@\#\!\$\%\^\&\]*";
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
          $regExpression = "(.*)";
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
          $length   = null;
        }

        if (YeAPF_TYPE_DATE == $keyType) {
          $minValue = null;
          $maxValue = null;
          $decimals = null;
          $length   = 10;
        }

        if (YeAPF_TYPE_TIME == $keyType) {
          $minValue = null;
          $maxValue = null;
          $decimals = null;
          $length   = 8;
        }

        if (YeAPF_TYPE_DATETIME == $keyType) {
          $minValue = null;
          $maxValue = null;
          $decimals = null;
          $length   = 19;
        }

        if (YeAPF_TYPE_FLOAT == $keyType) {
          if (null != $decimals) {
            if (null == $length) {
              throw new \YeAPF\YeAPFException("Length is required quen using decimals", YeAPF_LENGTH_IS_REQUIRED);
            }
          }
        }

        if (null !== $protobufOrder) {
          if (0 == $protobufOrder) {
            foreach ($this->__constraints as $key => $value) {
              if ($value["protobufOrder"] > $protobufOrder) {
                $protobufOrder = $value["protobufOrder"];
              }
            }
            $protobufOrder++;
            $protobufOrder = max(1, $protobufOrder);
          } else {
            foreach ($this->__constraints as $key => $value) {
              if ($protobufOrder == $value["protobufOrder"]) {
                new \YeAPF\YeAPFException("Protobuf order is not unique", YeAPF_PROTOBUF_ORDER_IS_NOT_UNIQUE);
              }
            }
          }
        }

        $this->__constraints[$keyName] = [
          "type"          => $keyType,
          "length"        => $length,
          "decimals"      => $decimals,
          "acceptNULL"    => $acceptNULL,
          "minValue"      => $minValue,
          "maxValue"      => $maxValue,
          "regExpression" => $regExpression,
          "unique"        => $unique,
          "required"      => $required,
          "primary"       => $primary,
          "protobufOrder" => $protobufOrder,
        ];
      }
    }

  }

  /**
   * Retrieve the constraint associated with the given key name.
   *
   * @param string $keyName The name of the key to retrieve the constraint for.
   * @return mixed The constraint associated with the given key name.
   */
  public function getConstraint(string $keyName) {
    return $this->__constraints[$keyName];
  }

  /**
   * Retrieves the constraints associated with this object.
   *
   * @return array The constraints associated with this object.
   */
  public function getConstraints() {
    return $this->__constraints;
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
  public function checkConstraint(string $keyName, mixed $value) {
    if (isset($this->__constraints[$keyName])) {
      $type       = $this->__constraints[$keyName]["type"];
      $length     = $this->__constraints[$keyName]["length"] ?? 0;
      $decimals   = $this->__constraints[$keyName]["decimals"] ?? 0;
      $acceptNULL = $this->__constraints[$keyName]["acceptNULL"] ?? false;
      $minValue   = $this->__constraints[$keyName]["minValue"] ?? null;
      $maxValue   = $this->__constraints[$keyName]["maxValue"] ?? null;

      if (null == $value && !$acceptNULL) {
        throw new \YeAPF\YeAPFException("Null not allowed in " . get_class() . " -> " . $keyName, YeAPF_NULL_NOT_ALLOWED);
      } else {
        if (YeAPF_TYPE_STRING == $type) {
          if ($length > 0 && strlen($value) > $length) {
            throw new \YeAPF\YeAPFException("String value too long in " . get_class() . "." . $keyName, YeAPF_INVALID_KEY_VALUE);
          }
        } elseif (YeAPF_TYPE_INT == $type) {
          if (!(null === $value && $acceptNULL)) {
            if (!is_int($value)) {
              throw new \YeAPF\YeAPFException("Invalid integer value in " . get_class() . "." . $keyName, YeAPF_INVALID_KEY_VALUE);
            }
            if (null !== $minValue) {
              if ($value < $minValue) {
                throw new \YeAPF\YeAPFException("Invalid integer value: out of bounds in " . get_class() . "." . $keyName, YeAPF_VALUE_OUT_OF_RANGE);
              }
            }

            if (null !== $maxValue) {
              if ($value > $maxValue) {
                throw new \YeAPF\YeAPFException("Invalid integer value: out of bounds in " . get_class() . "." . $keyName, YeAPF_VALUE_OUT_OF_RANGE);
              }
            }
          }
        } elseif (YeAPF_TYPE_FLOAT == $type) {
          if (!(null === $value && $acceptNULL)) {
            if (!is_numeric($value)) {
              throw new \YeAPF\YeAPFException("Invalid float value in " . get_class() . "." . $keyName, YeAPF_INVALID_KEY_VALUE);
            }
            if (null !== $minValue) {
              if ($value < $minValue) {
                throw new \YeAPF\YeAPFException("Invalid float value: out of bounds in " . get_class() . "." . $keyName, YeAPF_VALUE_OUT_OF_RANGE);
              }
            }
            if (null !== $maxValue) {
              if ($value > $maxValue) {
                throw new \YeAPF\YeAPFException("Invalid float value: out of bounds in " . get_class() . "." . $keyName, YeAPF_VALUE_OUT_OF_RANGE);
              }
            }
          }
        } elseif (YeAPF_TYPE_BOOL == $type) {
          if (!(null === $value && $acceptNULL)) {
            if (!is_bool($value)) {
              throw new \YeAPF\YeAPFException("Invalid boolean value in " . get_class() . "." . $keyName, YeAPF_INVALID_KEY_VALUE);
            }
          }
        } elseif (YeAPF_TYPE_DATE == $type) {
          if (!(null === $value && $acceptNULL)) {
            if (!is_string($value)) {
              throw new \YeAPF\YeAPFException("Invalid date type (string expected) in " . get_class() . "." . $keyName, YeAPF_INVALID_DATE_TYPE);
            } else {
              if (!preg_match(YeAPF_DATE_REGEX, $value)) {
                throw new \YeAPF\YeAPFException("Invalid date value in " . get_class() . "." . $keyName, YeAPF_INVALID_DATE_VALUE);
              }
              if (strlen($value) > $length) {
                throw new \YeAPF\YeAPFException("Date value too long in " . get_class() . "." . $keyName, YeAPF_VALUE_TOO_LONG);
              }
            }
          }
        } elseif (YeAPF_TYPE_DATETIME == $type) {
          if (!(null === $value && $acceptNULL)) {
            if (!is_string($value)) {
              throw new \YeAPF\YeAPFException("Invalid datetime type (string expected) in " . get_class() . "." . $keyName, YeAPF_INVALID_DATE_TYPE);
            } else {
              if (!preg_match(YeAPF_DATETIME_REGEX, $value)) {
                throw new \YeAPF\YeAPFException("Invalid datetime value in " . get_class() . "." . $keyName, YeAPF_INVALID_DATE_VALUE);
              }
            }
          }
        } elseif (YeAPF_TYPE_TIME == $type) {
          if (!(null === $value && $acceptNULL)) {
            if (!is_string($value)) {
              throw new \YeAPF\YeAPFException("Invalid time type (string expected) in " . get_class() . "." . $keyName, YeAPF_INVALID_DATE_TYPE);
            } else {
              if (!preg_match(YeAPF_TIME_REGEX, $value)) {
                throw new \YeAPF\YeAPFException("Invalid time value in " . get_class() . "." . $keyName, YeAPF_INVALID_DATE_VALUE);
              }
            }
          }
        } else {
          throw new \YeAPF\YeAPFException("Invalid key type in " . get_class() . "." . $keyName, YeAPF_INVALID_KEY_TYPE);
        }

      }
    }
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
  private function sanitize($value) {
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

  private function unsanitize($value) {
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
  public function __set(string $name, mixed $value) {
    $value = $this->sanitize($value);
    // \_log("  :: setting '$name' with ".print_r($value, true)."\n");
    $value = $this->checkConstraint($name, $value);
    parent::__set($name, $value);
  }

  public function __get(string $name) {
    // \_log("  :: getting '$name' -> ");
    $value = parent::__get($name);
    // \_log(print_r($value,true) ." -> ");
    $value = $this->unsanitize($value);
    // \_log(print_r($value, true)."\n");
    return $value;
  }

  public function __get_raw_value(string $name) {
    // \_log("  :: getting raw value '$name' -> ");
    $value = parent::__get($name);
    // \_log("$value\n");
    return $value;
  }

}

function translateObject($source, $destinationClassName) {
  $sourceReflection      = new \ReflectionObject($source);
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
