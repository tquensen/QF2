<?php
namespace QF;

class Config
{
    private $data = array();

    public function __construct($configFile = null)
    {
        if ($configFile) {
            $this->load($configFile);
        }
    }

    public function load($file)
    {
        if (file_exists($file)) {
            $config = &$this->data;
            include $file;
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
        
        $parts = explode('/', $key);
        $return = $this->data;
        if (count($parts) === 1) {
            return isset($return[$parts[0]]) ? $return[$parts[0]] : $default;
        } elseif (count($parts) === 2) {
            return isset($return[$parts[0]][$parts[1]]) ? $return[$parts[0]][$parts[1]] : $default;
        } elseif (count($parts) === 3) {
            return isset($return[$parts[0]][$parts[1]][$parts[2]]) ? $return[$parts[0]][$parts[1]][$parts[2]] : $default;
        }
        while (null !== ($index = array_shift($parts))) {
            if (isset($return[$index])) {
                $return = &$return[$index];
            } else {
                $return = $default;
                break;
            }
        }
        return $return;
        
    }

    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    public function set($key, $value = null)
    {
        if (is_array($value) && $key === null) {
            $this->data = $value;
        } else {            
            $parts = explode('/', $key);
            
            if (count($parts) === 1) {
                $this->data[$parts[0]] = $value;
            } elseif (count($parts) === 2) {
                $this->data[$parts[0]][$parts[1]] = $value;
            } elseif (count($parts) === 3) {
                $this->data[$parts[0]][$parts[1]][$parts[2]] = $value;
            } else {
                $pointer = &$this->data;
                while (null !== ($index = array_shift($parts))) {
                    if (count($parts) === 0) {
                        break;
                    }
                    if (!isset($pointer[$index])) {
                        $pointer[$index] = array();
                    }
                    $pointer = &$pointer[$index];
                }
                
                $pointer[$index] = $value;
            }
        }
    }
}