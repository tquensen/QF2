<?php
namespace Mongo;

class DB
{
    protected static $connections = array();
    protected static $settings = array();
    
    /**
     *
     * @return null
     */
    public static function init($settings = array())
    {
        self::$settings = $settings;
    }

    /**
     *
     * @return MongoDB
     */
    public static function get($connection = null)
    {
        if (!$connection) {
            $connection = 'default';
        }
        
        if (!isset(self::$connections[$connection])) {
            if (!isset(self::$settings[$connection])) {
                return;
            }

            if (!empty(self::$settings[$connection]['server'])) {
                $mongo = new Mongo(self::$settings[$connection]['server'], !empty(self::$settings[$connection]['options']) ? self::$settings[$connection]['options'] : array());
            } else {
                $mongo = new Mongo();
            }
            $database = !empty(self::$settings[$connection]['database']) ? self::$settings[$connection]['database'] : $connection;
            self::$connections[$connection] = $mongo->$database;
        }
        return isset(self::$connections[$connection]) ?self::$connections[$connection] : null;
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