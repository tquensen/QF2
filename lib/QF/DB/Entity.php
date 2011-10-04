<?php
namespace QF\DB;


abstract class Entity extends \QF\Entity
{
    protected $_databaseProperties = array();
    protected $_connection = null;

    protected static $tableName = '';
    protected static $autoIncrement = false;
    protected static $columns = array('id'); //array('id', 'name', 'user_id')
    protected static $relations = array();
    protected static $identifier = 'id';
    protected static $repositoryClass = '\\QF\\DB\\Repository';

    public function __construct($data = array(), $connection = null)
    {
        foreach ($data as $k => $v) {
            $this->set($k, $v);
        }
        $this->_connection = $connection;
    }
    
    public function isNew()
    {
        return empty($this->_databaseProperties);
    }

    public function getDatabaseProperty($key)
    {
        return isset($this->_databaseProperties[$key]) ? $this->_databaseProperties[$key] : null;
    }

    public function setDatabaseProperty($key, $value)
    {
        $this->_databaseProperties[$key] = $value;
    }

    public function clearDatabaseProperties()
    {
        $this->_databaseProperties = array();
    }
    
    public function serialize()
    {
        $data = array();
        foreach (array_keys(static::$_properties) as $prop) {
            $data[$prop] = $this->get($prop);
        }
        return serialize(array(
            'p' => $data,
            'dbp' => $this->_databaseProperties,
            'con' => $this->_connection
        ));
    }
    
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->__construct($data['p'], $data['con']);
        $this->_databaseProperties = $data['dbp'];
    }
    
    /**
     *
     * @return Repository
     */
    public function getRepository()
    {
        return new self::$repositoryClass($this->_connection, $this);
    }

    /**
     *
     * @return \PDO
     */
    public function getDB()
    {
        return $this->getRepository()->getDB();
    }
    
    public function getConnection()
    {
        return $this->_connection;
    }
    
    /**
     *
     * @return bool
     */
    public function save()
    {
        try {
            return $this->getRepository()->save($this);
        } catch (\Exception $e) {
            throw $e;
            return false;
        }
    }

    /**
     *
     * @return bool.
     */
    public function delete($safe = true)
    {
        try {
            return $this->getRepository()->remove($this);
        } catch (\Exception $e) {
            throw $e;
            return false;
        }
    }

    public function preSave()
    {

    }

    public function preRemove()
    {
        
    }

    public function postCreate()
    {

    }

    public function postLoad()
    {

    }
    
    public static function getidentifier()
    {
        return static::$identifier;
    }
    
    public static function getTableName()
    {
        return static::$tableName;
    }
    
    public static function isAutoIncrement()
    {
        return (bool) static::$autoIncrement;
    }
    
    public static function getColumns()
    {
        return static::$columns;
    }
    
    public static function getRelations()
    {
        return static::$relations;
    }
    
    public static function getRepositoryClass()
    {
        return static::$repositoryClass;
    }
}