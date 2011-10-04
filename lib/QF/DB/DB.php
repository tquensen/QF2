<?php
namespace QF\DB;

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

            $pdo = new PDO(
                self::$connections[$connection]['driver'],
                isset(self::$connections[$connection]['username']) ? self::$connections[$connection]['username'] : '',
                isset(self::$connections[$connection]['password']) ? self::$connections[$connection]['password'] : '',
                isset(self::$connections[$connection]['options']) ? self::$connections[$connection]['options'] : array()
            );
            
            if ($pdo && $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
                $pdo->exec('SET CHARACTER SET utf8');
            }
            
            self::$connections[$connection] = $pdo;
        }
        return isset(self::$connections[$connection]) ? self::$connections[$connection] : null;
    }
    
    public static function getRepository($entityClass, $connection = null)
    {
        if (is_object($entityClass)) {
            $entityClass = get_class($entityClass);
        }
            
        if (!is_subclass_of($this->entityClass, '\\QF\\DB\\Entity')) {
            throw new \InvalidArgumentException('$entityClass must be an \\QF\\DB\\Entity instance or classname');
        }
        
        $repositoryName = $entityClass::getRepositoryClass();
        return new $repositoryName($connection, $entityClass);
    }

}
