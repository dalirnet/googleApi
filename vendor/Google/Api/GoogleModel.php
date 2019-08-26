<?php

namespace Google\Api;

use ArrayAccess;
use ReflectionObject;
use ReflectionProperty;
use stdClass;

class GoogleModel implements ArrayAccess
{
    const NULL_VALUE = "{}gapi-php-null";
    protected $internal_gapi_mappings = array();
    protected $modelData = array();
    protected $processed = array();

    final public function __construct()
    {
        if (func_num_args() == 1 && is_array(func_get_arg(0))) {

            $array = func_get_arg(0);
            $this->mapTypes($array);
        }
        $this->gapiInit();
    }

    protected function mapTypes($array)
    {
        foreach ($array as $key => $val) {
            if (!property_exists($this, $this->keyType($key)) && property_exists($this, $key)) {
                $this->$key = $val;
                unset($array[$key]);
            } elseif (property_exists($this, $camelKey = $this->camelCase($key))) {

                $this->$camelKey = $val;
            }
        }
        $this->modelData = $array;
    }

    protected function keyType($key)
    {
        return $key . "Type";
    }

    private function camelCase($value)
    {
        $value = ucwords(str_replace(array('-', '_'), ' ', $value));
        $value = str_replace(' ', '', $value);
        $value[0] = strtolower($value[0]);
        return $value;
    }

    protected function gapiInit()
    {
        return;
    }

    public function toSimpleObject()
    {
        $object = new stdClass();
        foreach ($this->modelData as $key => $val) {
            $result = $this->getSimpleValue($val);
            if ($result !== null) {
                $object->$key = $this->nullPlaceholderCheck($result);
            }
        }
        $reflect = new ReflectionObject($this);
        $props = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($props as $member) {
            $name = $member->getName();
            $result = $this->getSimpleValue($this->$name);
            if ($result !== null) {
                $name = $this->getMappedName($name);
                $object->$name = $this->nullPlaceholderCheck($result);
            }
        }
        return $object;
    }

    private function getSimpleValue($value)
    {
        if ($value instanceof Google_Model) {
            return $value->toSimpleObject();
        } else if (is_array($value)) {
            $return = array();
            foreach ($value as $key => $a_value) {
                $a_value = $this->getSimpleValue($a_value);
                if ($a_value !== null) {
                    $key = $this->getMappedName($key);
                    $return[$key] = $this->nullPlaceholderCheck($a_value);
                }
            }
            return $return;
        }
        return $value;
    }

    private function getMappedName($key)
    {
        if (isset($this->internal_gapi_mappings) && isset($this->internal_gapi_mappings[$key])) {
            $key = $this->internal_gapi_mappings[$key];
        }
        return $key;
    }

    private function nullPlaceholderCheck($value)
    {
        if ($value === self::NULL_VALUE) {
            return null;
        }
        return $value;
    }

    public function assertIsArray($obj, $method)
    {
        if ($obj && !is_array($obj)) {
            throw new GoogleException("Incorrect parameter type passed to $method(). Expected an array.");
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->$offset) || isset($this->modelData[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->$offset) ? $this->$offset : $this->__get($offset);
    }

    public function __get($key)
    {
        $keyTypeName = $this->keyType($key);
        $keyDataType = $this->dataType($key);
        if (isset($this->$keyTypeName) && !isset($this->processed[$key])) {
            if (isset($this->modelData[$key])) {
                $val = $this->modelData[$key];
            } else if (isset($this->$keyDataType) && ($this->$keyDataType == 'array' || $this->$keyDataType == 'map')) {
                $val = array();
            } else {
                $val = null;
            }
            if ($this->isAssociativeArray($val)) {
                if (isset($this->$keyDataType) && 'map' == $this->$keyDataType) {
                    foreach ($val as $arrayKey => $arrayItem) {
                        $this->modelData[$key][$arrayKey] = $this->createObjectFromName($keyTypeName, $arrayItem);
                    }
                } else {
                    $this->modelData[$key] = $this->createObjectFromName($keyTypeName, $val);
                }
            } else if (is_array($val)) {
                $arrayObject = array();
                foreach ($val as $arrayIndex => $arrayItem) {
                    $arrayObject[$arrayIndex] = $this->createObjectFromName($keyTypeName, $arrayItem);
                }
                $this->modelData[$key] = $arrayObject;
            }
            $this->processed[$key] = true;
        }
        return isset($this->modelData[$key]) ? $this->modelData[$key] : null;
    }

    protected function dataType($key)
    {
        return $key . "DataType";
    }

    protected function isAssociativeArray($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $keys = array_keys($array);
        foreach ($keys as $key) {
            if (is_string($key)) {
                return true;
            }
        }
        return false;
    }

    private function createObjectFromName($name, $item)
    {
        $type = $this->$name;
        return new $type($item);
    }

    public function offsetSet($offset, $value)
    {
        if (property_exists($this, $offset)) {
            $this->$offset = $value;
        } else {
            $this->modelData[$offset] = $value;
            $this->processed[$offset] = true;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->modelData[$offset]);
    }

    public function __isset($key)
    {
        return isset($this->modelData[$key]);
    }

    public function __unset($key)
    {
        unset($this->modelData[$key]);
    }
}
