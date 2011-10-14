<?php
namespace QF\DB;

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
     * @return \PDO
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

            $pdo = new PDO(
                static::$settings[$connection]['driver'],
                isset(static::$settings[$connection]['username']) ? static::$settings[$connection]['username'] : '',
                isset(static::$settings[$connection]['password']) ? static::$settings[$connection]['password'] : '',
                isset(static::$settings[$connection]['options']) ? static::$settings[$connection]['options'] : array()
            );
            
            if ($pdo && $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
                $pdo->exec('SET CHARACTER SET utf8');
            }
            
            static::$connections[$connection] = $pdo;
        }
        return isset(static::$connections[$connection]) ? static::$connections[$connection] : null;
    }
    
    /**
     *
     * @param string|\QF\DB\Entity $entityClass (name of) an entity class
     * @param string|null $connection name of a connection
     * @return \QF\DB\Repository 
     */
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
