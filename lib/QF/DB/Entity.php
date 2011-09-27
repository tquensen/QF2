<?php
namespace QF\DB;

abstract class Entity implements \ArrayAccess, \Serializable
{
    protected final static $types = array('boolean' => true, 'bool' => true, 'integer' => true, 'int' => true, 'float' => true, 'double' => true, 'string' => true, 'array' => true);
    public static $properties = array(); //array('id' => 'integer, 'name' => 'string', 'user_id' => 'integer')
    public static $relations = array(); //array('user' => '\\Example\\\\Model\\User', 'comments' => array('\\Example\\\\Model\\Comment'))
    public static $table = '';
    public static $identifier = 'id';

    public function toArray($includeRelations = false)
    {
        $return = array();
        foreach (static::$properties as $prop => $type) {
            $property = $this->get($prop);
            if (is_object($property) && method_exists($property, 'toArray')) {
                $property = $property->toArray();
            }
            $return[$prop] = $property;
        }
        if ($includeRelations) {
            foreach (static::$relations as $rel => $type) {
                $property = $this->get($rel);
                if (is_array($property)) {
                    foreach ($property as $k => $v) {
                        if (is_object($v) && method_exists($v, 'toArray')) {
                            $property[$k] = $v->toArray();
                        }
                    }
                } elseif (is_object($property) && method_exists($property, 'toArray')) {
                    $property = $property->toArray();
                }
                $return[$rel] = $property;
            }
        }
        return $return;
    }
    
    public function serialize()
    {
        $data = array();
        foreach (static::$properties as $prop => $type) {
            $data[$prop] = $this->get($prop);
        }
        foreach (static::$relations as $rel => $type) {
            $data[$rel] = $this->get($rel);
        }
        return serialize($data);
    }
    
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->__construct();
        foreach ($data as $k => $v) {
            $this->__set($k, $v);
        }
    }
    
    public function offsetSet($offset, $data)
    {
        $this->__set($offset, $data);
    }

    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }
    
    public function __get($property)
    {
        return $this->get($property);
    }
    
    public function __set($property, $value)
    {
        $this->set($property, $value);
    }
    
    public function __isset($property) {
        return $this->is($property);
    }
    
    public function __unset($property) {
        if (array_key_exists($property, static::$properties)) {
            $this->$property = null;
        } elseif(array_key_exists($property, static::$relations)) {
            $this->$property = null;
        }
    }
    
    public function get($property)
    {
        if (array_key_exists($property, static::$properties)) {
            return isset($this->$property) ? $this->$property : null;
        } elseif(array_key_exists($property, static::$relations)) {
            return isset($this->$property) ? $this->$property : null;
        } else {
            $trace = debug_backtrace();
            throw new \UneException('Trying to get undefined property: '.get_class($this).'::$' . $property .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
            return null;
        }
    }
    
    public function set($property, $value)
    {
        if (array_key_exists($property, static::$properties)) {
            if ($value !== null && is_string(static::$properties[$property])) {
                if (isset(static::$types[static::$properties[$property]])) {            
                    settype($value, static::$types[static::$properties[$property]]);
                } elseif (!($value instanceof static::$properties[$property])) {
                    $trace = debug_backtrace();
                    throw new \UnexpectedValueException('Error setting property: '.get_class($this).'::$' . $property .
                        ' must be of type '.static::$properties[$property] .', '.get_class($value).' given in ' . $trace[0]['file'] .
                        ' on line ' . $trace[0]['line']);
                }
            }
            $this->$property = $value;
        } elseif(array_key_exists($property, static::$relations)) {
            if ($value !== null) {
                if (is_string(static::$relations[$property])) {
                    if (isset(static::$types[static::$relations[$property]])) {            
                        settype($value, static::$types[static::$relations[$property]]);
                    } elseif (!($value instanceof static::$relations[$property])) {
                        $trace = debug_backtrace();
                        throw new \UnexpectedValueException('Error setting property: '.get_class($this).'::$' . $property .
                            ' must be of type '.static::$properties[$property] .', '.get_class($value).' given in ' . $trace[0]['file'] .
                            ' on line ' . $trace[0]['line']);
                    }
                } elseif (is_array(static::$relations[$property]) && isset(static::$relations[$property][0])) {
                    if (!is_array($value) && !($value instanceof \Traversable)) {
                        $value = array($value);
                    }
                    foreach ($value as $val) {
                        if (!($val instanceof static::$relations[$property][0])) {
                            $trace = debug_backtrace();
                            throw new \UnexpectedValueException('Error setting property: '.get_class($this).'::$' . $property .
                                ' must be an array of '.static::$properties[$property][0] .', '.get_class($val).' given in ' . $trace[0]['file'] .
                                ' on line ' . $trace[0]['line']);
                        }
                    }
                }
            }
            $this->$property = $value;
        } else {
            $trace = debug_backtrace();
            throw new \Exception('Trying to set undefined property: '.get_class($this).'::$' . $property .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        }
    }
    
    public function add($property, $value)
    {
        if(array_key_exists($property, static::$relations) && is_array(static::$relations[$property]) && isset(static::$relations[$property][0])) {
            if (!is_array($value) && !($value instanceof \Traversable)) {
                $value = array($value);
            }
            $current = $this->$property;
            if (!is_array($current) && !($current instanceof \ArrayAccess)) {
                $trace = debug_backtrace();
                    throw new \UnexpectedValueException('Error adding a value to property: '.get_class($this).'::$' . $property .
                        ' the property must be an array or implenent \\ArrayAccess to allow adding values in ' . $trace[0]['file'] .
                        ' on line ' . $trace[0]['line']);
            }
            foreach ($value as $val) {
                if (!($val instanceof static::$relations[$property][0])) {
                    $trace = debug_backtrace();
                    throw new \UnexpectedValueException('Error adding a value to property: '.get_class($this).'::$' . $property .
                        ' must be of type '.static::$properties[$property][0] .', '.get_class($val).' given in ' . $trace[0]['file'] .
                        ' on line ' . $trace[0]['line']);
                } else {
                    $current[] = $val;
                }
            }
        } else {
            $trace = debug_backtrace();
            throw new \Exception('Trying to add a value to undefined property: '.get_class($this).'::$' . $property .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        }
    }
    
    public function is($property) {
        if (array_key_exists($property, static::$properties) || array_key_exists($property, static::$relations)) {
            return isset($this->$property);
        } else {
            return false;
        }
    }

    public function __call($method, $args)
    {
        if (substr($method, 0, 3) == 'get') {
            return $this->get(lcfirst(substr($method, 3)));
        } elseif (substr($method, 0, 3) == 'set') {
            return $this->set(lcfirst(substr($method, 3)), isset($args[0]) ? $args[0] : null);
        } elseif (substr($method, 0, 3) == 'add') {
            return $this->add(lcfirst(substr($method, 3)), isset($args[0]) ? $args[0] : null);
        } elseif (substr($method, 0, 2) == 'is') {
            return (bool) $this->is(lcfirst(substr($method, 2)));
        } else {
            $trace = debug_backtrace();
            throw new \Exception('Call to undefined method: '.get_class($this).'::'.$method.'().' .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        }
    }
}