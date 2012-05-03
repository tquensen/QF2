<?php
namespace QF\Mongo;

abstract class Entity extends \QF\Entity
{
    protected $_databaseProperties = array();
    protected $_db = null;
    
    protected static $columns = array();
    protected static $relations = array();

    protected static $collectionName = null;
    protected static $autoId = false;
    protected static $repositoryClass = '\\QF\\Mongo\\Repository';
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
                          //assumes 1:n or n:m relation if collection is set, 1:1 or n:1 otherwise
            'relationMultiple' => true //set to true for m:n relations (when either local_column or foreign_columns is an array) default = false
        ),
         */
        '_id'        => array('type' => '\\MongoId', 'column' => true),
    );
    
    /**
     * @var \MongoId
     */
    protected $_id;
    
    public function __construct($db = null)
    {
        $this->_db = $db;
    }
    
    public function __call($method, $args)
    {
        if (preg_match('/^(count|load|link|unlink)(.+)$/', $method, $matches)) {
            $action = $matches[1];
            $property = lcfirst($matches[2]);
            if (static::getRelation($property)) {
                if ($action == 'load') {
                    return $this->loadRelated($property, isset($args[0]) ? $args[0] : array(), isset($args[1]) ? $args[1] : array(), isset($args[2]) ? $args[2] : null, isset($args[3]) ? $args[3] : null);
                } elseif ($action == 'count') {
                    return $this->countRelated($property, isset($args[0]) ? $args[0] : array(), isset($args[1]) ? $args[1] : array());
                } elseif ($action == 'link') {
                    return $this->linkRelated($property, isset($args[0]) ? $args[0] : null, array_key_exists(1, $args) ? $args[1] : true, array_key_exists(2, $args) ? $args[2] : false);
                } else {
                    return $this->unlinkRelated($property, array_key_exists(0, $args) ? $args[0] : true, array_key_exists(1, $args) ? $args[1] : false, array_key_exists(2, $args) ? $args[2] : true);
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
     * @return MongoDB
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
     * @return MongoCollection
     */
    public function getCollection($db = null)
    {
        if (!$db) {
            $db = $this->getDB();
        }
        return $this->getRepository($db)->getCollection();
    }

    public function increment($property, $value, $save = null)
    {
        $this->set($property, $this->get($property) + $value);
        if ($save !== null) {
            $status = $this->getRgetCollection()->update(array('_id' => $this->_id), array('$inc' => array($property => $value)), array('safe' => $save));
            if ($status) {
                $this->setDatabaseProperty($property, $this->$property);
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     *
     * @param string $relation the relation name
     * @param array $query Additional fields to filter.
     * @param array $sort The fields by which to sort.
     * @param int $limit The number of results to return.
     * @param int $skip The number of results to skip.
     * @return Entity|array
     */
    public function loadRelated($relation, $query = array(), $sort = array(), $limit = null, $skip = null)
    {
        if (!$relationInfo = static::getRelation($relation)) {
            throw new \Exception('Unknown relation "'.$relation.'" for model '.get_class($this));
        }
        
        $repository = $relationInfo[0]::GetRepository($this->getDB());
            
        if (!empty($relationInfo[3])) {
            $query = array_merge(array($relationInfo[2] => $this->{$relationInfo[1]}), (array) $query);            
            $related = $repository->findOne($query);
            $this->set($relation, $related);
            return $related;
        } else {
            $query = (array) $query;
            if ($relationInfo[2] == '_id' && (isset($relationInfo[3]) && $relationInfo[3] === false)) {
                $query[$relationInfo[2]] = array('$in' => (array) $this->{$relationInfo[1]});
            } else {
                $query[$relationInfo[2]] = $this->{$relationInfo[1]};
            }            
            $related = $repository->find($query, $sort, $limit, $skip);
            $this->set($relation, $related);
            return $related;
        }
    }
    
    /**
     *
     * @param string $relation the relation name
     * @param array $query Additional fields to filter.
     * @param string $saveAs save the result in this property (Example: 'fooCount' to save as $this->fooCount) property must exist!
     * @return int
     */
    public function countRelated($relation, $query = array(), $saveAs = null)
    {
        if (!$relationInfo = static::getRelation($relation)) {
            throw new \Exception('Unknown relation "'.$relation.'" for model '.get_class($this));
        }
        
        $repository = $relationInfo[0]::GetRepository($this->getDB());
            
        if (!empty($relationInfo[3])) {
            $query = array_merge(array($relationInfo[2] => $this->{$relationInfo[1]}), (array) $query);            
            $return = $repository->count($query);
        } else {
            $query = (array) $query;
            if ($relationInfo[2] == '_id' && (isset($relationInfo[3]) && $relationInfo[3] === false)) {
                $query[$relationInfo[2]] = array('$in' => (array) $this->{$relationInfo[1]});
            } else {
                $query[$relationInfo[2]] = $this->{$relationInfo[1]};
            }            
            $return = $repository->count($query, $sort, $limit, $skip);
        }
        
        if ($saveAs) {
            $this->$saveAs = $return;
        }
        return $return;
    }

    /**
     *
     * @param string $relation the relation name
     * @param Mongo_Model|mixed $related either a Mongo\Model object, a Mongo\Model->_id-value or an array with multiple Mongo\Models
     * @param mixed $save set to null to prevent a save() call, otherwise call save($save)
     * @return bool
     */
    public function linkRelated($relation, $related, $save = true)
    {
        if (!$relationInfo = static::getRelation($relation)) {
            throw new \Exception('Unknown relation "'.$relation.'" for model '.get_class($this));
        }
        if (is_array($related)) {
            foreach ($related as $rel) {
                $this->linkRelated($relation, $rel, $save, $multiple);
            }
            return true;
        }
        if (!is_object($related) || !($related instanceof Entity)) {
            $repository = $relationInfo[0]::getRepository($this->getDB());
            $related = $repository->findOne($related);
            if (!$related) {
                throw new \InvalidArgumentException('Could not find valid '.$relationInfo[0]);
            }
        }
        if (!empty($relationInfo[3])) {
            if ($relationInfo[1] == '_id') {
                if (!$this->{$relationInfo[1]}) {
                    if (!static::isAutoId()) {
                        throw new \Exception('Counld not link realted '.$relationInfo[0].' - '.$relationInfo[1].' not set!');
                    }
                    $this->{$relationInfo[1]} = new \MongoId();
                    if ($save !== null) {
                        $this->save($save);
                    }
                }
                
                $related->{$relationInfo[2]} = $this->{$relationInfo[1]};
                return $save !== null ? $related->save($save) : true;
            } elseif ($relationInfo[2] == '_id') {
                if (!$related->{$relationInfo[2]}) {
                    if (!$relationInfo[0]::isAutoId()) {
                        throw new \Exception('Counld not link realted '.$relationInfo[0].' - '.$relationInfo[2].' not set!');
                    }
                    $related->{$relationInfo[2]} = new \MongoId();
                    if ($save !== null) {
                        $related->save($save);
                    }
                }
                $this->{$relationInfo[1]} = $related->{$relationInfo[2]};
                return $save !== null ? $this->save($save) : true;
            }
        } else {
            if ($relationInfo[1] == '_id' && !$this->{$relationInfo[1]}) {
                if (!static::isAutoId()) {
                    throw new \Exception('Couldnt not link realted '.$relationInfo[0].' - '.$relationInfo[1].' not set!');
                }
                $this->{$relationInfo[1]} = new \MongoId();
                if ($save !== null) {
                    $this->save($save);
                }
            } elseif ($relationInfo[2] == '_id' && !$related->{$relationInfo[2]}) {
                if (!$relationInfo[0]::isAutoId()) {
                    throw new \Exception('Couldnt not link realted '.$relationInfo[0].' - '.$relationInfo[2].' not set!');
                }
                $related->{$relationInfo[2]} = new \MongoId();
                if ($save !== null) {
                    $related->save($save);
                }
            }
            if (isset($relationInfo[3]) && $relationInfo[3] === false) {
                $multiple = true;
            }
            if ($relationInfo[1] == '_id') {
                if ($multiple) {
                    $rels = (array) $related->{$relationInfo[2]};
                    if (!in_array($this->{$relationInfo[1]}, $rels)) {
                        $rels[] = $this->{$relationInfo[1]};
                        $rels = array_values($rels);
                        $related->{$relationInfo[2]} = $rels;
                    }
                } else {
                    $related->{$relationInfo[2]} = $this->{$relationInfo[1]};                    
                }
                return $save !== null ? $related->save($save) : true;
            } else {
                if ($multiple) {
                    $rels = (array) $this->{$relationInfo[1]};
                    if (!in_array($related->{$relationInfo[2]}, $rels)) {
                        $rels[] = $related->{$relationInfo[2]};
                        $rels = array_values($rels);
                        $this->{$relationInfo[1]} = $rels;
                    }
                } else {
                    $this->{$relationInfo[1]} == $related->{$relationInfo[2]};
                }
                return $save !== null ? $this->save($save) : true;
            }
        }
    }

    /**
     *
     * @param string $relation the relation name
     * @param \Mongo\Entity|mixed $related true to unlink all objects or either a \Mongo\Entity object, a \Mongo\Entity->_id-value  or an array with multiple \Mongo\Entity
     * @param boolean $delete true to delete the related entry, false to only remove the relation (default false) 
     * @param mixed $save set to null to prevent a save() call, otherwise call save($save)
     * @return bool
     */
    public function unlinkRelated($relation, $related = true, $delete = false, $save = true)
    {
        if (!$relationInfo = static::getRelation($relation)) {
            throw new \Exception('Unknown relation "'.$relation.'" for model '.get_class($this));
        }
        if (is_array($related)) {
            foreach ($related as $rel) {
                $this->unlinkRelated($relation, $rel, $delete, $save);
            }
            return true;
        }
        if (!empty($relationInfo[3])) {
            
            $repository = $relationInfo[0]::GetRepository($this->getDB());
                
            if ($relationInfo[1] == '_id') {
                if (!$this->{$relationInfo[1]} || $save === null) {
                    return true;
                }
                
                $query = array($relationInfo[2] => $this->{$relationInfo[1]});
                $options = $save !== null ? array('safe' => $save) : array();
                if ($related !== true) {
                    if (!is_object($related) || !($related instanceof Entity)) {
                        $query['_id'] = $related;
                    } else {
                        $query['_id'] = $related->_id;
                    }
                }
                if ($delete) {
                    return $repository->getCollection()->remove($query, $options);
                } else {
                    return $repository->getCollection()->update($query, array('$set' => array($relationInfo[2] => null)), $options);
                }
            } else {
                if ($related !== true) {
                    if (is_object($related) && $related instanceof Entity) {
                        $related = $related->_id;
                    }
                    if ($this->{$relationInfo[1]} != $related) {
                        return false;
                    }
                }
                
                if ($delete) {
                    $query = array($relationInfo[2] => $this->{$relationInfo[1]});
                    $options = $save !== null ? array('safe' => $save) : array();
                    if (!$repository->getCollection()->remove($query, $options)) {
                        return false;
                    }    
                }
                $this->{$relationInfo[1]} = null;
                return $save !== null ? $this->save($save) : true;
            }
        } else {
            if (isset($relationInfo[3]) && $relationInfo[3] === false) {
                $multiple = true;
            }
            if ($related === true) {
                if ($relationInfo[2] == '_id') {                    
                    if ($delete) {
                        $repository = $relationInfo[0]::getRepository($this->getDB());
                        
                        $options = $save !== null ? array('safe' => $save) : array();
                        if ($multiple) {
                            $status = (bool) $repository->getCollection()->remove(array($relationInfo[2] => array('$in' => $this->{$relationInfo[1]})), $options);
                        } else {
                            $status = (bool) $repository->getCollection()->remove(array($relationInfo[2] => $this->{$relationInfo[1]}), $options);
                        }
                        
                        if (!$status) {
                            return false;
                        }                       
                    }
                    $this->{$relationInfo[1]} = null;
                    return $save !== null ? $this->save($save) : true;
                } else {
                    $repository = $relationInfo[0]::GetRepository($this->getDB());
                    $related = $repository->find(array($relationInfo[2] => $this->{$relationInfo[1]}));
                    foreach ($related as $rel) {
                        if ($multiple) {
                            $rels = $related->{$relationInfo[2]};
                            if ($k = array_search($this->{$relationInfo[1]}, $rels)) {
                                unset($rels[$k]);
                                $rels = array_values($rels);
                            }
                            $rel->{$relationInfo[2]} = $rels;
                        } else {
                            $rel->{$relationInfo[2]} = null;
                        }
                        if ($delete && !$rel->{$relationInfo[2]} && $save !== null) {
                            $rel->remove($save);
                        } elseif ($save !== null) {
                            $rel->save($save);
                        }
                    }
                }
                return true;
            } else {
                if (!is_object($related) || !($related instanceof Entity)) {
                    $repository = $relationInfo[0]::GetRepository($this->getDB());
                    $related = $repository->findOne($related);
                }
                if (!$related) {
                    return false;
                }
                if ($related->{$relationInfo[2]} != $this->{$relationInfo[1]} && !is_array($related->{$relationInfo[2]}) && !is_array($this->{$relationInfo[1]})) {
                    return false;
                }
                if ($relationInfo[1] == '_id') {
                    if ($multiple) {
                        $rels = $related->{$relationInfo[2]};
                        if ($k = array_search($this->{$relationInfo[1]}, $rels)) {
                            unset($rels[$k]);
                            $rels = array_values($rels);
                        }
                        $related->{$relationInfo[2]} = $rels;
                    } elseif($related->{$relationInfo[2]} == $this->{$relationInfo[1]}) {
                        $related->{$relationInfo[2]} = null;
                    } else {
                        return false;
                    }
                    if ($delete && !$related->{$relationInfo[2]} && $save !== null) {
                        $related->remove($save);
                    } elseif ($save !== null) {
                        return $related->save($save);
                    } else {
                        return true;
                    }
                } else {
                    if ($multiple) {
                        $rels = $this->{$relationInfo[1]};
                        if ($k = array_search($related->{$relationInfo[2]}, $rels)) {
                            unset($rels[$k]);
                            $rels = array_values($rels);
                        }
                        $this->{$relationInfo[1]} = $rels;
                    } elseif($related->{$relationInfo[2]} == $this->{$relationInfo[1]}) {
                        $this->{$relationInfo[1]} = null;
                    } else {
                        return false;
                    }
                    if ($delete && $save !== null) {
                        $related->remove($save);
                    } 
                    return $save !== null ? $this->save($save) : true;
                }
            }
        }
    }

    /**
     *
     * @param bool|integer $safe @see php.net/manual/en/mongocollection.update.php
     * @return bool Returns if the update was successfully sent to the database.
     */
    public function save($safe = true, $db = null)
    {
        if (!$db) {
            $db = $this->getDB();
        }
        try {
            return static::getRepository($db)->save($this, $safe);
        } catch (\Exception $e) {
            throw $e;
            return false;
        }
    }

    /**
     *
     * @param bool|integer $safe @see php.net/manual/en/mongocollection.remove.php
     * @return mixed If "safe" is set, returns an associative array with the status of the remove ("ok"), the number of items removed ("n"), and any error that may have occured ("err"). Otherwise, returns TRUE if the remove was successfully sent, FALSE otherwise.
     */
    public function delete($safe = true, $db = null)
    {
        if (!$db) {
            $db = $this->getDB();
        }
        try {
            return static::getRepository($db)->remove($this, $safe);
        } catch (\Exception $e) {
            throw $e;
            return false;
        }
    }

    public function preSave(\MongoDB $db)
    {

    }

    public function preRemove(\MongoDB $db)
    {
        
    }

    public function postCreate()
    {

    }

    public function postLoad()
    {

    }
    
    public static function getCollectionName()
    {
        return static::$collectionName;
    }
    
    public static function isAutoId()
    {
        return (bool) static::$autoId;
    }
    
    public static function getColumns()
    {
        if (!isset(static::$columns[static::$collectionName])) {
            $cols = array();        
            foreach (static::$_properties as $prop => $data) {
                if (!empty($data['column'])) {
                    $cols[] = $prop;
                }
            }
            static::$columns[static::$collectionName] = $cols;
        }
        return static::$columns[static::$collectionName];
    }
    
    public static function getRelation($rel)
    {
        if (!isset(static::$relations[static::$collectionName])) {
            $rels = array();          
            foreach (static::$_properties as $prop => $data) {
                if (!empty($data['relation']) && !empty($data['type']) && !empty($data['relation'][0]) && !empty($data['relation'][1])) {
                    $rel = array($data['type'], $data['relation'][0], $data['relation'][1]);
                    if (empty($data['collection'])) {
                        $rel[3] = true;
                    } elseif (!empty($data['relationMultiple'])) {
                        $rel[3] = false;
                    }
                    $rels[] = $rel;
                }
            }
            static::$relations[static::$collectionName] = $rels;
        }
        return !empty(static::$relations[static::$collectionName][$rel]) ? static::$relations[static::$collectionName][$rel] : false;
    }
    
    public static function getRelations()
    {
        if (!isset(static::$relations[static::$collectionName])) {
            $rels = array();          
            foreach (static::$_properties as $prop => $data) {
                if (!empty($data['relation']) && !empty($data['type']) && !empty($data['relation'][0]) && !empty($data['relation'][1])) {
                    $rel = array($data['type'], $data['relation'][0], $data['relation'][1]);
                    if (empty($data['collection'])) {
                        $rel[3] = true;
                    }elseif (!empty($data['relationMultiple'])) {
                        $rel[3] = false;
                    }
                    $rels[] = $rel;
                }
            }
            static::$relations[static::$collectionName] = $rels;
        }
        return static::$relations[static::$collectionName];
    }
    
    public static function getRepositoryClass()
    {
        return static::$repositoryClass;
    }
    
    /**
     * initiate the collection for this model
     */
    public static function install($db, $installedVersion = 0, $targetVersion = 0)
    {
        return;
        
        //EXAMPLE / copy&paste
        $collection = static::getRepository($db)->getCollection();
        switch ($installedVersion) {
            case 0:
                $collection->ensureIndex(array('slug' => 1), array('safe' => true, 'unique' => true));
            case 1:
                if ($targetVersion && $targetVersion <= 1) break;
            /* //for every new version add your code below (including the lines "case NEW_VERSION:" and "if ($targetVersion && $targetVersion <= NEW_VERSION) break;")

                $collection->ensureIndex(array('name' => 1), array('safe' => true));

            case 2:
                if ($targetVersion && $targetVersion <= 2) break;
             */
        }
        return true;
    }

    /**
     * remove the collection for this model
     */
    public static function uninstall($db, $installedVersion = 0, $targetVersion = 0)
    {
        return;
        
        //EXAMPLE / copy&paste
        $collection = static::getRepository($db)->getCollection();
        SWITCH ($installedVersion) {
            case 0:
            /* //for every new version add your code directly below "case 0:", beginning with "case NEW_VERSION:" and "if ($targetVersion >= NEW_VERSION) break;"
            case 2:
                if ($targetVersion >= 2) break;
                $collection->deleteIndex("name");
             */
            case 1:
                if ($targetVersion >= 1) break;
                $collection->drop();
        }
        return true;
    }
}