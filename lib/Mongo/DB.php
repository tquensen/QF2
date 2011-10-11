<?php
namespace Mongo;

class DB
{
    protected static $connections = array();
    protected static $settings = array();
    
    public function __construct($settings = array())
    {
        static::init($settings);
    }
    
    public static function init($settings = array())
    {
        static::$settings = $settings;
    }

    /**
     *
     * @return \MongoDB
     */
    public static function get($connection = null)
    {
        if (!$connection) {
            $connection = 'default';
        }
        
        if (!isset(static::$connections[$connection])) {
            if (!isset(static::$settings[$connection])) {
                return;
            }

            if (!empty(static::$settings[$connection]['server'])) {
                $mongo = new Mongo(static::$settings[$connection]['server'], !empty(static::$settings[$connection]['options']) ? static::$settings[$connection]['options'] : array());
            } else {
                $mongo = new Mongo();
            }
            $database = !empty(static::$settings[$connection]['database']) ? static::$settings[$connection]['database'] : $connection;
            self::$connections[$connection] = $mongo->$database;
        }
        return isset(static::$connections[$connection]) ? static::$connections[$connection] : null;
    }
    
    public static function getRepository($entityClass, $connection = null)
    {
        if (is_object($entityClass)) {
            $entityClass = get_class($entityClass);
        }
            
        if (!is_subclass_of($this->entityClass, '\\Mongo\\Entity')) {
            throw new \InvalidArgumentException('$entityClass must be an \\Mongo\\Entity instance or classname');
        }
        
        $repositoryName = $entityClass::getRepositoryClass();
        return new $repositoryName($connection, $entityClass);
    }

    
}