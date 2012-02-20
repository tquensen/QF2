<?php
namespace QF\DB;


abstract class Entity extends \QF\Entity
{
    protected $_databaseProperties = array();
    protected $_db = null;
    
    protected static $columns = array();
    protected static $relations = array();

    protected static $tableName = '';
    protected static $autoIncrement = false;
    protected static $identifier = 'id';
    protected static $repositoryClass = '\\QF\\DB\\Repository';
    protected static $_properties = array(
        /* example
        'property' => array(
            'type' => 'string', //a scalar type or a classname, true to allow any type, default = true
            'container' => 'data', //the parent-property containing the property ($this->container[$property]) or false ($this->$property), default = false
            'readonly' => false, //only allow read access (get, has, is)
            'required' => false, //disallow unset(), clear(), and set(null), default = false (unset(), clear(), and set(null) is allowed regardles of type) - the property can still be null if not initialized!
            'collection' => true, //stores multiple values, activates add and remove methods, true to store values in an array, name of a class that implements ArrayAccess to store values in that class, default = false (single value),
            'collectionUnique' => true, //do not allow dublicate entries when using as collection, when type = array or an object and collectionUnique is a string, that property/key will be used as index of the collection
            'collectionRemoveByValue' => true, //true to remove entries from a collection by value, false to remove by key, default = false, this only works if collection is an array or an object implementing Traversable
            'collectionSingleName' => false, //alternative property name to use for add/remove actions, default=false (e.g. if property = "children" and collectionSingleName = "child", you can use addChild/removeChild instead of addChildren/removeChildren)
            'exclude' => true, //set to true to exclude this property on toArray() and foreach(), default = false
            'default' => null, // the default value to return by get if null, and to set by clear, default = null
    
            'column' => true, //true if this property is a database column (default false)
            'relation' => array(local_column, foreign_column), //database relation or false for no relation, default = false
                          //assumes 1:n or n:m relation if collection is set, n:1 or 1:1 otherwise
            'refTable' => 'tablename' //for n:m relations - the name of the ref table, default = false
        )
         */
    );
    
    public function __construct($db = null)
    {
        $this->_db = $db;
    }
    
    public function __call($method, $args)
    {
        if (preg_match('/^(load|link|unlink)(.+)$/', $method, $matches)) {
            $action = $matches[1];
            $property = lcfirst($matches[2]);
            if (static::getRelation($property)) {
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
            'dbp' => $this->_databaseProperties
        ));
    }
    
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->__construct();
        foreach ($data['p'] as $k => $v) {
            $this->$k = $v;
        }
        $this->_databaseProperties = $data['dbp'];
    }
    
    /**
     *
     * @return Repository
     */
    public static function getRepository($db)
    {
        return new static::$repositoryClass($db, get_called_class());
    }

    /**
     *
     * @return \PDO
     */
    public function getDB()
    {
        return $this->_db;
    }
    
    public function setDB($db)
    {
        $this->_db = $db;
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

            $repository = $data[0]::getRepository($this->getDB());
            $stmt = $this->getDB()->prepare('SELECT '.$data[2].' FROM '.$data[3].' WHERE '.$data[1].'= ?')->execute(array($this->{static::getIdentifier()}));
            $refTableIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $values = array_merge($refTableIds, (array) $values);
            $condition = (array) $condition;
            array_unshift($condition, $data[0]::getIdentifier().' IN ('.implode(',', array_fill(0, count($refTableIds), '?')).')');
            $entries = $repository->load($condition, $values, $order, $limit, $offset);
        } else {
            $values = (array) $values;
            array_unshift($values, $this->{$data[1]});
            $condition = (array) $condition;
            array_unshift($condition, $data[2].' = ?');
            $repository = $data[0]::getRepository($this->getDB());
            $entries = $repository->load($condition, $values, $order, $limit, $offset);
        }

        if (isset($data[3]) && $data[3] === true) {
            $entries = reset($entries);
            $this->$relation = $entries;
        } else {
            foreach ($entries as $entry) {
                $this->add($relation, $entry);
            }
        }

        return $entries;
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

        $repository = $data[0]::getRepository($this->getDB());
        if (!isset($data[3]) || $data[3] === true) {
            if ($data[1] == static::getIdentifier()) {
                if (!$this->{static::getIdentifier()}) {
                    $this->save();
                }
                if (is_object($identifier)) {
                    $identifier->{$data[2]} = $this->getIdentifier();
                    $identifier->save();
                } else {
                    $this->getDB()->prepare('UPDATE '.$data[0]::getTableName().' SET '.$data[2].' = ? WHERE '.$data[0]::getIdentifier().' = ?')->execute(array($this->{static::getIdentifier()}, $identifier));
                }
            } elseif ($data[2] == $data[0]::getIdentifier()) {
                if (is_object($identifier)) {
                    if (!$identifier->{$data[0]::getIdentifier()}) {
                        $identifier->save();
                    }
                    $this->{$data[1]} = $identifier->{$data[0]::getIdentifier()};
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
        $repository = $data[0]::getRepository($this->getDB());
        if (!isset($data[3]) || $data[3] === true) {
            if ($data[1] == static::getIdentifier()) {
                if (is_object($identifier)) {                  
                    if ($delete) {
                        $identifier->delete();
                    } else {
                        $identifier->{$info[2]} = null;
                        $identifier->save();
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
            } elseif ($data[2] == static::getIdentifier()) {
                $this->{$data[1]} = null;
                $this->save();
                if ($delete && is_object($identifier)) {
                    $identifier->delete();
                }
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
    public function save($db = null)
    {
        if (!$db) {
            $db = $this->getDB();
        }
        try {
            return static::getRepository($db)->save($this);
        } catch (\Exception $e) {
            throw $e;
            return false;
        }
    }

    /**
     *
     * @return bool.
     */
    public function delete($db = null)
    {
        if (!$db) {
            $db = $this->getDB();
        }
        try {
            return static::getRepository($db)->remove($this);
        } catch (\Exception $e) {
            throw $e;
            return false;
        }
    }

    public function preSave(\PDO $db)
    {

    }

    public function preRemove(\PDO $db)
    {
        
    }

    public function postCreate()
    {

    }

    public function postLoad()
    {

    }
    
    public static function getIdentifier()
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
        if (!isset(static::$columns[static::$tableName])) {
            $cols = array();        
            foreach (static::$_properties as $prop => $data) {
                if (!empty($data['column'])) {
                    $cols[] = $prop;
                }
            }
            static::$columns[static::$tableName] = $cols;
        }
        if ($prefix) {
            $cols = array();
            foreach (static::$columns[static::$tableName] as $col) {
                $cols[] = $prefix.'.'.$col.' '.$prefix.'_'.$col;
            }
            return $cols;
        }
        return static::$columns[static::$tableName];
    }
    
    public static function getRelation($rel)
    {
        if (!isset(static::$relations[static::$tableName])) {
            $rels = array();          
            foreach (static::$_properties as $prop => $data) {
                if (!empty($data['relation']) && !empty($data['type']) && !empty($data['relation'][0]) && !empty($data['relation'][1])) {
                    $rel = array($data['type'], $data['relation'][0], $data['relation'][1]);
                    if (!empty($data['refTable'])) {
                        $rel[3] = $data['refTable'];
                    } elseif (empty($data['collection'])) {
                        $rel[3] = true;
                    }
                    $rels[] = $rel;
                }
            }
            static::$relations[static::$tableName] = $rels;
        }
        return !empty(static::$relations[static::$tableName][$rel]) ? static::$relations[static::$tableName][$rel] : false;
    }
    
    public static function getRelations()
    {
        if (!isset(static::$relations[static::$tableName])) {
            $rels = array();          
            foreach (static::$_properties as $prop => $data) {
                if (!empty($data['relation']) && !empty($data['type']) && !empty($data['relation'][0]) && !empty($data['relation'][1])) {
                    $rel = array($data['type'], $data['relation'][0], $data['relation'][1]);
                    if (!empty($data['refTable'])) {
                        $rel[3] = $data['refTable'];
                    } elseif (empty($data['collection'])) {
                        $rel[3] = true;
                    }
                    $rels[] = $rel;
                }
            }
            static::$relations[static::$tableName] = $rels;
        }
        return static::$relations[static::$tableName];
    }
    
    public static function getRepositoryClass()
    {
        return static::$repositoryClass;
    }
}