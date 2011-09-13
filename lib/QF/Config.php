<?php
namespace QF;

class Config
{
    private $data = array();

    public function __construct($configFile = null)
    {
        if ($configFile) {
            $this->data = (array) $this->load($configFile);
        }
    }
    
    public function merge($file, &$previous)
    {
        if (file_exists($file)) {
            $newData = include $file;
            $previous = array_merge((array) $previous, (array) $newData);
        }
        return $previous;
    }
    
    public function load($file)
    {
        if (file_exists($file)) {
            $newData = include $file;
            return (array) $newData;
        }
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $data;
        }
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    public function set($key, $value = null)
    {
        if (is_array($key) && $value === null) {
            $this->data = $key;
        } else {
            $this->data[$key] = $value;
        }
    }
}