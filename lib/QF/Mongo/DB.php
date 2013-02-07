<?php
namespace QF\Mongo;

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
     * @return \MongoDB
     */
    public function get()
    {
        if ($this->connection === null) {

            if (!empty($this->settings['server']) || !empty($this->settings['options'])) {
                $mongo = new \MongoClient(!empty($this->settings['server']) ? $this->settings['server'] : 'mongodb://localhost:27017', !empty($this->settings['options']) ? $this->settings['options'] : array());
            } else {
                $mongo = new \MongoClient();
            }
            $database = !empty($this->settings['database']) ? $this->settings['database'] : 'default';
            $this->connection = $mongo->$database;
        }
        return $this->connection;
    }
    
    /**
     *
     * @param string|\QF\Mongo\Entity $entityClass (name of) an entity class
     * @return \QF\Mongo\Repository 
     */
    public function getRepository($entityClass)
    {
        if (is_object($entityClass)) {
            $entityClass = get_class($entityClass);
        }
            
        if (!is_subclass_of($entityClass, '\\Mongo\\Entity')) {
            throw new \InvalidArgumentException('$entityClass must be an \\Mongo\\Entity instance or classname');
        }
        
        return $entityClass::getRepository($this->get());
    }

    
}