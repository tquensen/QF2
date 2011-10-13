<?php
namespace QF\DB;


abstract class Entity extends \QF\Entity
{
    protected $_databaseProperties = array();
    protected $_connection = null;

    protected static $tableName = '';
    protected static $autoIncrement = false;
    protected static $columns = array('id'); //array('id', 'name', 'user_id')
    protected static $relations = array(); //array of array(ForeignClassName, local_column, foreign_column, [true=foreign is single (for m:1 or 1:1), string = name of ref table (for m:n), leave blank=foreign is multiple (for 1:m)])
    protected static $identifier = 'id';
    protected static $repositoryClass = '\\QF\\DB\\Repository';

    public function __construct($data = array(), $connection = null)
    {
        foreach ($data as $k => $v) {
            $this->set($k, $v);
        }
        $this->_connection = $connection;
    }
    
    public function __call($method, $args)
    {
        if (preg_match('/^(load|link|unlink)(.+)$/', $method, $matches)) {
            $action = $matches[1];
            $property = lcfirst($matches[2]);
            if (isset(self::$relations[$property])) {
                if ($action == 'load') {
                    return $this->loadRelated($property, isset($args[0]) ? $args[0] : null, isset($args[1]) ? $args[1] : array(), isset($args[2]) ? $args[2] : null, isset($args[3]) ? $args[3] : null, isset($args[4]) ? $args[4] : null);
                } elseif ($action == 'link') {
                    return $this->linkRelated($property, isset($args[0]) ? $args[0] : null);
                } else {
                    return $this->unlinkRelated($property, array_key_exists(0, $args) ? $args[0] : true, array_key_exists(1, $args) ? $args[1] : false);
                }
            }
        }
        return parent::__call($method, $args);
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
     * @param string $relation the name of a relation
     * @param string $condition the where-condition
     * @param array $values values for the placeholders in the condition
     * @param string $order the order
     * @param int $limit the limit
     * @param int $offset the offset
     * @return MiniMVC_Model|MiniMVC_Collection
     */
    public function loadRelated($relation, $condition = null, $values = array(), $order = null, $limit = null, $offset = null)
    {
        if (!$data = static::getRelation($relation)) {
            throw new \Exception('Unknown relation "'.$relation.'" for entity '.get_class($this));
        }
        if (!is_array($values)) {
            $values = (array) $values;
        }

        if (isset($data[3]) && $data[3] !== true) {
            array_unshift($values, $this->getIdentifier());
            $repositoryName = $data[0]::getRepositoryClass();
            $repository = new $repositoryName($this->_connection, $data[0]);
            $query = $this->getDB->prepare('SELECT '.$data[2].' FROM '.$data[3].' WHERE '.$data[1].'= ?')->execute(array($this->{static::getIdentifier()}));
            $relTableIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $values = array_merge($refTableIds, (array) $values);
            $condition = (array) $condition;
            array_unshift($condition, $data[0]::getIdentifier().' IN ('.implode(',', array_fill(0, count($refTableIds), '?')).')');
            $entries = $repository->load($condition, $values, $order, $limit, $offset);
        } else {
            $values = (array) $values;
            array_unshift($values, $this->{$data[1]});
            $condition = (array) $condition;
            array_unshift($condition, $data[2].' = ?');
            $repositoryName = $data[0]::getRepositoryClass();
            $repository = new $repositoryName($this->_connection, $data[0]);
            $entries = $repository->load($condition, $values, $order, $limit, $offset);
        }

        $this->$relation = $entries;

        return (isset($data[3]) && $data[3] === true) ? reset($entries) : $entries;
    }
    
    /**
     *
     * @param string $relation the name of a relation
     * @param mixed $identifier a related model object, the identifier of a related model
     * @param bool $loadRelated whether to load the related object (if identifier is not already loaded and assigned to this model)
     */
    public function linkRelated($relation, $identifier = null)
    {
        if (is_array($identifier)) {
            foreach ($identifier as $id) {
                $this->linkRelated($relation, $id, $loadRelated);
            }
            return true;
        }
        
        if (!$identifier) {
            throw new Exception('No identifier/related '.$relation.' given for model '.get_class($this));
        }
        if (!$data = static::getRelation($relation)) {
            throw new Exception('Unknown relation "'.$relation.'" for model '.get_class($this));
        }

        $repositoryName = $data[0]::getRepositoryClass();
        $repository = new $repositoryName($this->_connection, $data[0]);
        if (!isset($data[3]) || $data[3] === true) {
            if ($data[1] == static::getIdentifier()) {
                if (!$this->{static::getIdentifier()}) {
                    $this->save();
                }
                if (is_object($identifier)) {
                    $identifier->{$data[2]} = $this->getIdentifier();
                    $identifier->save();
                } elseif (isset($this->$relation[$identifier])) {
                    $this->$relation[$identifier]->{$info[2]} = $this->{static::getIdentifier()};
                    $this->$relation[$identifier]->save();
                } else {
                    $this->getDB()->prepare('UPDATE '.$data[0]::getTableName().' SET '.$data[2].' = ? WHERE '.$data[0]::getIdentifier().' = ?')->execute(array($this->{static::getIdentifier()}, $identifier));
                }
            } elseif ($data[2] == $data[0]::getIdentifier()) {
                if (is_object($identifier)) {
                    if (!$identifier->{$data[0]::getIdentifier()}) {
                        $identifier->save();
                    }
                    $this->{$data[1]} = $identifier->{$data[0]::getIdentifier()};
                } elseif(isset($this->$relation[$identifier])) {
                    if (!$this->$relation[$identifier]->{$data[0]::getIdentifier()}) {
                         $this->$relation[$identifier]->save();
                    }
                    $this->{$data[1]} = $this->$relation[$identifier]->{$data[0]::getIdentifier()};
                } else {
                    $this->{$data[1]} = $identifier;
                }
                $this->save();
            }
        } else {
            if (is_object($identifier)) {
                if (!$this->{static::getIdentifier()}) {
                    $this->save();
                }
                if (!$identifier->{$data[0]::getIdentifier()}) {
                    $identifier->save();
                }
                $stmt = $this->getDB()->prepare('SELECT id, '.$data[1].', '.$data[2].' FROM '.$data[3].' WHERE '.$data[1].' = ? AND '.$data[2].' = ?')->execute(array($this->{static::getIdentifier()}, $identifier->{$data[0]::getIdentifier()}));
                $result = $stmt->fetch(PDO::FETCH_NUM);
                $stmt->closeCursor();
                if (!$result) {
                    $this->getDB()->prepare('INSERT INTO '.$data[3].' ('.$data[1].', '.$data[2].') VALUES (?,?)')->execute(array($this->{static::getIdentifier()}, $identifier->{$data[0]::getIdentifier()}));
                }
            } else {
                $stmt = $this->getDB()->prepare('SELECT id, '.$data[1].', '.$data[2].' FROM '.$data[3].' WHERE '.$data[1].' = ? AND '.$data[2].' = ?')->execute(array($this->{static::getIdentifier()}, $identifier));
                $result = $stmt->fetch(PDO::FETCH_NUM);
                $stmt->closeCursor();
                if (!$result) {
                    $this->getDB()->prepare('INSERT INTO '.$data[3].' ('.$data[1].', '.$data[2].') VALUES (?,?)')->execute(array($this->{static::getIdentifier()}, $identifier));
                }
            }
        }
    }
    
    /**
     *
     * @param string $relation the name of a relation
     * @param mixed $identifier a related model object, the identifier of a related model or true to unlink all related models
     */
    public function unlinkRelated($relation, $identifier = true, $delete = false)
    {
        if (is_array($identifier)) {
            foreach ($identifier as $id) {
                $this->unlinkRelated($relation, $id);
            }
            return true;
        }

        if (!$data = static::getRelation($relation)) {
            throw new Exception('Unknown relation "'.$relation.'" for model '.get_class($this));
        }
        $repositoryName = $data[0]::getRepositoryClass();
        $repository = new $repositoryName($this->_connection, $data[0]);
        if (!isset($data[3]) || $data[3] === true) {
            if ($data[1] == static::getIdentifier()) {
                if (is_object($identifier)) {                  
                    if ($delete) {
                        $identifier->delete();
                    } else {
                        $identifier->{$info[2]} = null;
                        $identifier->save();
                    }   
                } elseif (isset($this->$relation[$identifier])) {
                    if ($delete) {
                        $this->$relation[$identifier]->delete();
                    } else {
                        $this->$relation[$identifier]->{$info[2]} = null;
                        $this->$relation[$identifier]->save();
                    }                       
                } elseif($identifier === true) {
                    if (!$this->{static::getIdentifier()}) {
                        return false;
                    }
                    if ($delete) {
                        $this->getDB()->prepare('DELETE FROM '.$data[0]::getTableName().' WHERE '.$data[2].' = ?')->execute(array($this->{static::getIdentifier()}));
                    } else {
                        $this->getDB()->prepare('UPDATE '.$data[0]::getTableName().' SET '.$data[2].' = ? WHERE '.$data[2].' = ?')->execute(array(null, $this->{static::getIdentifier()}));
                    }
                } else {
                    if (!$this->{static::getIdentifier()}) {
                        return false;
                    }
                    if ($delete) {
                        $this->getDB()->prepare('DELETE FROM '.$data[0]::getTableName().' WHERE '.$data[0]::getIdentifier().' = ? AND '.$data[2].' = ?')->execute(array($identifier, $this->{static::getIdentifier()}));
                    } else {
                        $this->getDB()->prepare('UPDATE '.$data[0]::getTableName().' SET '.$data[2].' = ? WHERE '.$data[0]::getIdentifier().' = ? AND '.$data[2].' = ?')->execute(array(null, $identifier, $this->{static::getIdentifier()}));
                    }
                }
            } elseif ($data[2] == $table->getIdentifier()) {
                $this->{$data[1]} = null;
                $this->save();
            }
        } else {
            if (!$this->{static::getIdentifier()}) {
                return false;
            }
            if ($identifier === true) {
                if ($delete) {
                    $stmt = $this->getDB()->prepare('SELECT '.$data[2].' FROM '.$data[3].' WHERE '.$data[1].' = ?')->execute(array($this->{static::getIdentifier()}));
                    $refTableIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $this->getDB()->prepare('DELETE FROM '.$data[0]::getTableName().' WHERE '.$data[0]::getIdentifier().' IN ('.implode(',', array_fill(0, count($refTableIds), '?')).')')->execute($refTableIds);
                }
                $this->getDB()->prepare('DELETE FROM '.$data[3].' WHERE '.$data[1].' = ?')->execute(array($this->{static::getIdentifier()}));
            } else {
                if ($delete) {
                    if (is_object($identifier)) {
                        $identifier->delete();
                    } else {
                        $this->getDB()->prepare('DELETE FROM '.$data[0]::getTableName().' WHERE '.$data[0]::getIdentifier().' = ? LIMIT 1')->execute(array($identifier));
                    }
                }
                $this->getDB()->prepare('DELETE FROM '.$data[3].' WHERE '.$data[1].' = ? AND '.$data[2].' = ?')->execute(array($this->{static::getIdentifier()}, is_object($identifier) ? $identifier->{$data[0]::getIdentifier()} : $identifier));
            }
        }
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
    public function delete()
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
    
    public static function getColumns($prefix = null)
    {
        if ($prefix) {
            $cols = array();
            foreach (static::$columns as $col) {
                $cols[] = $prefix.'.'.$col.' '.$prefix.'_'.$col;
            }
            return $cols;
        }
        return static::$columns;
    }
    
    public static function getRelation($rel)
    {
        return !empty(static::$relations[$rel]) ? static::$relations[$rel] : false;
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