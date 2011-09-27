<?php

abstract class Entity implements ArrayAccess, Serializable, IteratorAggregate
{
    protected static $_types = array('boolean' => true, 'bool' => true, 'integer' => true, 'int' => true, 'float' => true, 'double' => true, 'string' => true, 'array' => true);
    protected static $_properties = array(
        'property' => array(
            'type' => 'string', //a scalar type or a classname, true to allow any type, default = true
            'container' => 'data', //the parent-property containing the property ($this->container[$property]) or false ($this->$property), default = false
            'readonly' => false, //only allow read access (get, has, is)
            'collection' => true, //stores multiple values, activates add and remove methods, true to store values in an array, name of a class that implements ArrayAccess to store values in that class, default = false (single value),
            'collectionUnique' => true, //do not allow dublicate entries when using as collection, when type = array or an object and collectionUnique is a string, that property/key will be used as index of the collection
            'collectionRemoveByValue' => true, //true to remove entries from a collection by value, false to remove by key, default = false, this only works if collection is an array or an object implementing Traversable
            'exclude' => true, //set to true to exclude this property on toArray() and foreach(), default = false
            'default' => null // the default value to return by get if null, and to set by clear, default = null
        )
    );
    
    public function get($property) {
        $method = 'get'.ucfirst($property);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        if (empty(static::$_properties[$property])) {
            $trace = debug_backtrace();
            throw new Exception('Trying to get undefined property: '.get_class($this).'::$'.$property .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        }
        
        if (!empty(static::$_properties[$property]['container'])) {
            if (!isset($this->{static::$_properties[$property]['container']}[$property])) {
                return isset(static::$_properties[$property]['default']) ? static::$_properties[$property]['default'] : null;
            }
            return $this->{static::$_properties[$property]['container']}[$property];
        } else {
            if ($this->$property === null && isset(static::$_properties[$property]['default'])) {
                return isset(static::$_properties[$property]['default']) ? static::$_properties[$property]['default'] : null;
            }
            return $this->$property;
        }
    }
    
    public function set($property, $value) {
        $method = 'set'.ucfirst($property);
        if (method_exists($this, $method)) {
            return $this->$method($value);
        }
        if (empty(static::$_properties[$property])) {
            $trace = debug_backtrace();
            throw new Exception('Trying to set undefined property: '.get_class($this).'::$'.$property .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        } elseif (!empty(static::$_properties[$property]['readonly'])) {
            $trace = debug_backtrace();
            throw new Exception('Trying to set readonly property: '.get_class($this).'::$'.$property .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        }
        if ($value === null) {
            return $this->clear($property);
        }
        
        if (!empty(static::$_properties[$property]['collection'])) {
            if (is_string(static::$_properties[$property]['collection'])) {
                if (!is_object($value) || !($value instanceof static::$_properties[$property]['collection'])) {
                    $trace = debug_backtrace();
                    throw new UnexpectedValueException('Error setting property: '.get_class($this).'::$' . $property .
                        ' must be of type '.static::$_properties[$property]['type'] .', '.(is_object($value) ? get_class($value) : gettype($value)).' given in ' . $trace[0]['file'] .
                        ' on line ' . $trace[0]['line']);
                }
            } else {
                if (!is_array($value) && (!is_object($value) || !($value instanceof ArrayAccess))) {
                    $trace = debug_backtrace();
                    throw new UnexpectedValueException('Error setting property: '.get_class($this).'::$' . $property .
                        ' must be of type array or ArrayAccess, '.(is_object($value) ? get_class($value) : gettype($value)).' given in ' . $trace[0]['file'] .
                        ' on line ' . $trace[0]['line']);
                }
            }
        } else {
            if (!empty(static::$_properties[$property]['type']) && is_string(static::$_properties[$property]['type'])) {
                if (isset(self::$_types[static::$_properties[$property]['type']])) {            
                    settype($value, static::$_properties[$property]['type']);
                } elseif (!is_object($value) || !($value instanceof static::$_properties[$property]['type'])) {
                    $trace = debug_backtrace();
                    throw new UnexpectedValueException('Error setting property: '.get_class($this).'::$' . $property .
                        ' must be of type '.static::$_properties[$property]['type'] .', '.(is_object($value) ? get_class($value) : gettype($value)).' given in ' . $trace[0]['file'] .
                        ' on line ' . $trace[0]['line']);
                }
            }
        }
        
        if (!empty(static::$_properties[$property]['container'])) {
            $this->{static::$_properties[$property]['container']}[$property] = $value;
        } else {
            $this->$property = $value;
        }
        
    }
    
    public function add($property, $value) {
        $method = 'add'.ucfirst($property);
        if (method_exists($this, $method)) {
            return $this->$method($value);
        }
        if (empty(static::$_properties[$property])) {
            $trace = debug_backtrace();
            throw new Exception('Trying to add to undefined property: '.get_class($this).'::$'.$property .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        } elseif (empty(static::$_properties[$property]['collection'])) {
            $trace = debug_backtrace();
            throw new Exception('Trying to add to non-collection property: '.get_class($this).'::$'.$property .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        } elseif (!empty(static::$_properties[$property]['readonly'])) {
            $trace = debug_backtrace();
            throw new Exception('Trying to add to readonly property: '.get_class($this).'::$'.$property .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        }
        
        if (!empty(static::$_properties[$property]['type']) && is_string(static::$_properties[$property]['type'])) {
            if (isset(self::$_types[static::$_properties[$property]['type']])) {            
                settype($value, static::$_properties[$property]['type']);
            } elseif (!is_object($value) || !($value instanceof static::$_properties[$property]['type'])) {
                $trace = debug_backtrace();
                throw new UnexpectedValueException('Error adding to property: '.get_class($this).'::$' . $property .
                    ' must be of type '.static::$_properties[$property]['type'] .', '.(is_object($value) ? get_class($value) : gettype($value)).' given in ' . $trace[0]['file'] .
                    ' on line ' . $trace[0]['line']);
            }
        }
        
        if (!empty(static::$_properties[$property]['container'])) {
            if (!isset($this->{static::$_properties[$property]['container']}[$property])) {
                $this->{static::$_properties[$property]['container']}[$property] = static::$_properties[$property]['collection'] === true ? array() : new static::$_properties[$property]['collection'];
            }
            if (empty(static::$_properties[$property]['collectionUnique'])) {
                $this->{static::$_properties[$property]['container']}[$property][] = $value;
            } elseif (static::$_properties[$property]['collectionUnique'] === true) {
                if (false !== ($k = array_search($value, $this->{static::$_properties[$property]['container']}[$property], true))) {
                    $this->{static::$_properties[$property]['container']}[$property][$k] = $value;
                } else {
                    $this->{static::$_properties[$property]['container']}[$property][] = $value;
                }
            } else {
                if (is_array($value) || (is_object($value) && $value instanceof ArrayAccess)) {
                    $this->{static::$_properties[$property]['container']}[$property][$value[static::$_properties[$property]['collectionUnique']]] = $value;
                } elseif(is_object($value)) {
                    $this->{static::$_properties[$property]['container']}[$property][$value->{static::$_properties[$property]['collectionUnique']}] = $value;
                } else {
                    if (false !== ($k = array_search($value, $this->{static::$_properties[$property]['container']}[$property], true))) {
                        $this->{static::$_properties[$property]['container']}[$property][$k] = $value;
                    } else {
                        $this->{static::$_properties[$property]['container']}[$property][] = $value;
                    }
                }
            }
        } else {
            if (!isset($this->$property)) {
                $this->$property = static::$_properties[$property]['collection'] === true ? array() : new static::$_properties[$property]['collection'];
            }
            if (empty(static::$_properties[$property]['collectionUnique'])) {
                $this->{$property}[] = $value;
            } elseif (static::$_properties[$property]['collectionUnique'] === true) {
                if (false !== ($k = array_search($value, $this->$property, true))) {
                    $this->{$property}[$k] = $value;
                } else {
                    $this->{$property}[] = $value;
                }
            } else {
                if (is_array($value) || (is_object($value) && $value instanceof ArrayAccess)) {
                    $this->{$property}[$value[static::$_properties[$property]['collectionUnique']]] = $value;
                } elseif(is_object($value)) {
                    $this->{$property}[$value->{static::$_properties[$property]['collectionUnique']}] = $value;
                } else {
                    if (false !== ($k = array_search($value, $this->$property, true))) {
                        $this->{$property}[$k] = $value;
                    } else {
                        $this->{$property}[] = $value;
                    }
                }
            }
        }
        
        
        
    }
    
    public function remove($property, $value) {
        $method = 'remove'.ucfirst($property);
        if (method_exists($this, $method)) {
            return $this->$method($value);
        }
        if (empty(static::$_properties[$property])) {
            $trace = debug_backtrace();
            throw new Exception('Trying to remove from undefined property: '.get_class($this).'::$'.$property .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        } elseif (empty(static::$_properties[$property]['collection'])) {
            $trace = debug_backtrace();
            throw new Exception('Trying to remove from non-collection property: '.get_class($this).'::$'.$property .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        } elseif (!empty(static::$_properties[$property]['readonly'])) {
            $trace = debug_backtrace();
            throw new Exception('Trying to remove from readonly property: '.get_class($this).'::$'.$property .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        }
        
        if (!empty(static::$_properties[$property]['container'])) {
            if (empty($this->{static::$_properties[$property]['container']}[$property])) {
                return;
            }
            if (empty(static::$_properties[$property]['collectionRemoveByValue'])) {
                unset($this->{static::$_properties[$property]['container']}[$property]);
            } else {
                if ($this->{static::$_properties[$property]['container']}[$property]) {
                    if (false !== ($k = array_search($value, $this->{static::$_properties[$property]['container']}[$property], true))) {
                        unset($this->{static::$_properties[$property]['container']}[$property][$k]);
                    }
                } elseif (is_object($this->{static::$_properties[$property]['container']}[$property]) && $this->{static::$_properties[$property]['container']}[$property] instanceof Traversable) {
                    foreach ($this->{static::$_properties[$property]['container']}[$property] as $k => $v) {
                        if ($v === $value) {
                            unset($this->{static::$_properties[$property]['container']}[$property][$k]);
                            break;
                        }
                    }
                }
            }
        } else {
            if (empty($this->$property)) {
                return;
            }
            if (empty(static::$_properties[$property]['collectionRemoveByValue'])) {
                unset($this->{$property}[$value]);
            } else {
                if (is_array($this->$property)) {
                    if (false !== ($k = array_search($value, $this->$property, true))) {
                        unset($this->{$property}[$k]);
                    }
                } elseif (is_object($this->$property) && $this->$property instanceof Traversable) {
                    foreach ($this->$property as $k => $v) {
                        if ($v === $value) {
                            unset($this->{$property}[$k]);
                            break;
                        }
                    }
                }
            }
        }
        
        
        
        
    }
    
    public function clear($property) {
        $method = 'clear'.ucfirst($property);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        if (empty(static::$_properties[$property])) {
            $trace = debug_backtrace();
            throw new Exception('Trying to clear undefined property: '.get_class($this).'::$'.$property .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        } elseif (!empty(static::$_properties[$property]['readonly'])) {
            $trace = debug_backtrace();
            throw new Exception('Trying to clear readonly property: '.get_class($this).'::$'.$property .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        }

        if (!empty(static::$_properties[$property]['container'])) {
            $this->{static::$_properties[$property]['container']}[$property] = isset(static::$_properties[$property]['default']) ? static::$_properties[$property]['default'] : null;
        } else {
            $this->$property = isset(static::$_properties[$property]['default']) ? static::$_properties[$property]['default'] : null;
        }
    }
    
    public function is($property) {
        $method = 'is'.ucfirst($property);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        if (empty(static::$_properties[$property])) {
            return false;
        }
        if (!empty(static::$_properties[$property]['container'])) {
            return !empty ($this->{static::$_properties[$property]['container']}[$property]);
        } else {
            return !empty ($this->$property);
        }        
    }
    
    public function has($property)
    {
        return $this->is($property);
    }
    
    public function toArray($exclude = array(), $recursive = true)
    {
        $exclude = array_flip((array) $exclude);
        $return = array();
        foreach (array_keys(static::$_properties) as $prop) {
            if (isset($exclude[$prop]) || !empty(static::$_properties[$prop]['exclude'])) {
                continue;
            }
            $property = $this->get($prop);
            if ($recursive) {
                if (is_array($property) || (is_object($property) && $property instanceof Traversable)) {
                        foreach ($property as $k => $v) {
                            if (is_object($v) && method_exists($v, 'toArray')) {
                                $property[$k] = $v->toArray();
                            }
                        }
                } elseif (is_object($property)) {
                    if (method_exists($property, 'toArray')) {
                         $property = $property->toArray();
                    }
                }
            }
            $return[$prop] = $property;
        }
        return $return;
    }
    
    public function getIterator() {
        return new ArrayIterator($this->toArray(array(), false));
    }
    
    public function serialize()
    {
        $data = array();
        foreach (array_keys(static::$_properties) as $prop) {
            $data[$prop] = $this->$prop;
        }
        return serialize($data);
    }
    
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
    }
    
    public function offsetSet($offset, $data)
    {
        $this->set($offset, $data);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetExists($offset)
    {
        return $this->is($offset);
    }

    public function offsetUnset($offset)
    {
        $this->clear($offset);
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
        return $this->clear($property);
    }
    
    public function __call($method, $args)
    {
        if (preg_match('/^(get|set|clear|has|is|add|remove)(.+)$/', $method, $matches)) {
            $action = $matches[1];
            $property = lcfirst($matches[2]);
            if ($action == 'set' || $action == 'add' || $action == 'remove') {
                return $this->$action($property, isset($args[0]) ? $args[0] : null);
            } else {
                return $this->$action($property);
            }
            
        } else {
            $trace = debug_backtrace();
            throw new Exception('Call to undefined method: '.get_class($this).'::'.$method.'()' .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        }
    }
}
