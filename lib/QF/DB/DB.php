<?php
namespace QF\DB;

class DB
{
    protected $connection = null;
    protected $settings = array();
    
    public function __construct($settings = array())
    {
        $this->settings = $settings;
    }

    /**
     *
     * @return \PDO
     */
    public function get()
    {   
        if ($this->connection === null) {
            $pdo = new \PDO(
                $this->settings['driver'],
                isset($this->settings['username']) ? $this->settings['username'] : '',
                isset($this->settings['password']) ? $this->settings['password'] : '',
                isset($this->settings['options']) ? $this->settings['options'] : array()
            );
            
            if ($pdo && $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) == 'mysql') {
                $pdo->exec('SET CHARACTER SET utf8');
            }
            
            $this->connection = $pdo;
        }
        return $this->connection;
    }
    
    /**
     *
     * @param string|\QF\DB\Entity $entityClass (name of) an entity class
     * @return \QF\DB\Repository 
     */
    public function getRepository($entityClass)
    {
        if (is_object($entityClass)) {
            $entityClass = get_class($entityClass);
        }
            
        if (!is_subclass_of($entityClass, '\\QF\\DB\\Entity')) {
            throw new \InvalidArgumentException('$entityClass must be an \\QF\\DB\\Entity instance or classname');
        }
        
        return $entityClass::getRepository($this->get());
    }

}
