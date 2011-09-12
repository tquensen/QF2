<?php
namespace QF;

class Config
{
    private $data = array();

    public function __construct($data = array())
    {
        $this->data = $data;
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