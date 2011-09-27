<?php
namespace Mongo;

abstract class Entity implements \ArrayAccess, \Serializable
{

    protected $_properties = array();
    protected $_databaseProperties = array();
    protected $_connection = null;
    
    protected static abstract $collectionName = null;
    protected static abstract $autoId = false;
    protected static abstract $columns = array();
    protected static abstract $relations = array();
    protected static abstract $embedded = array();
    protected static $repositoryClass = '\\Mongo\\Repository';

    public function __construct($data = array(), $connection = null)
    {
        $this->_properties = $data;
        $this->_connection = $connection;
    }
    
    public function offsetSet($offset, $data)
    {
        $this->set($offset, $data);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetExists($offset)
    {
        return $this->is($offset);
    }

    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __set($key, $value)
    {
        $this->set($key, $value);
    }
    
    public function __isset($key)
	{
        return $this->is($key);
	}

    public function __unset($key)
	{
        $this->remove($key);
	}
    
    public function get($key)
    {
        if (array_key_exists($key, static::$columns)) {
            return isset($this->_properties[$key]) ? $this->_properties[$key] : null;
        } elseif(array_key_exists($key, static::$relations)) {
            $args = func_get_args();
            return call_user_func_array(array($this, 'getRelated'), $args);
        } elseif(array_key_exists($key, static::$embedded)) {
            $args = func_get_args();
            return call_user_func_array(array($this, 'getEmbedded'), $args);
        } else {
            $trace = debug_backtrace();
            throw new \UneException('Trying to get undefined property: '.get_class($this).'::$' . $property .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
            return null;
        }
        return isset($this->_properties[$key]) ? $this->_properties[$key] : null;
    }

    public function set($key, $value)
    {
        if (array_key_exists($key, static::$columns)) {
            $this->_properties[$key] = $value;
        } elseif(array_key_exists($property, static::$relations)) {
            $args = func_get_args();
            return call_user_func_array(array($this, 'setRelated'), $args);
        } elseif(array_key_exists($property, static::$embedded)) {
            $args = func_get_args();
            return call_user_func_array(array($this, 'setEmbedded'), $args);
        } else {
            $trace = debug_backtrace();
            throw new \Exception('Trying to set undefined property: '.get_class($this).'::$' . $property .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        }
    }
    
    public function is($key)
	{
        return isset($this->_properties[$key]);
	}

    public function remove($key)
	{
        if (array_key_exists($key, static::$columns) || isset($this->_properties[$key])) {
           unset($this->_properties[$key]);
        } elseif(array_key_exists($key, static::$relations)) {
            $args = func_get_args();
            return call_user_func_array(array($this, 'removeRelated'), $args);
        } elseif(array_key_exists($key, static::$embedded)) {
            $args = func_get_args();
            return call_user_func_array(array($this, 'removeEmbedded'), $args);
        }
	}
    
    public function __call($method, $args)
    {
        if (substr($method, 0, 3) == 'get') {
            array_unshift($args, lcfirst(substr($method, 3)));
            return call_user_func_array(array($this, 'get'), $args);
        } elseif (substr($method, 0, 3) == 'set') {
            array_unshift($args, lcfirst(substr($method, 3)));
            return call_user_func_array(array($this, 'set'), $args);
        } elseif (substr($method, 0, 2) == 'is') {
            return $this->is(lcfirst(substr($method, 2)));
        } elseif (substr($method, 0, 6) == 'remove') {
            array_unshift($args, lcfirst(substr($method, 3)));
            return call_user_func_array(array($this, 'remove'), $args);
        } else {
            $trace = debug_backtrace();
            throw new \Exception('Call to undefined method: '.get_class($this).'::'.$method.'().' .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line']);
        }
    }

    public function &getData()
    {
        return $this->_properties;
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

    public function toArray()
    {
        return $this->getData();
    }
    
    public function serialize()
    {
        return serialize(array(
            'p' => $this->_properties,
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
        return new self::$repositoryClass($this, $this->_connection);
    }

    /**
     *
     * @return MongoDB
     */
    public function getDB()
    {
        return $this->getRepository()->getDB();
    }

    /**
     *
     * @return MongoCollection
     */
    public function getCollection()
    {
        return $this->getRepository()->getCollection();
    }
    
    public function getConnection()
    {
        return $this->_connection;
    }

    public function increment($property, $value, $save = null)
    {
        $this->$property = $this->property + $value;
        if ($save !== null) {
            $status = $this->getCollection()->update(array('_id' => $this->_id), array('$inc' => array($property => $value)), array('safe' => $save));
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
     * @return Mongo_Model|array
     */
    public function getRelated($relation, $query = array(), $sort = array(), $limit = null, $skip = null)
    {
        if (!$relationInfo = static::getRelation($relation)) {
            throw new Exception('Unknown relation "'.$relation.'" for model '.get_class($this));
        }
        $repositoryName = $relationInfo[0]::GetRepositoryClass();
        $repository = new $repositoryName($relationInfo[0], $this->getConnection());
            
        if (!empty($relationInfo[3])) {
            return $repository->findOne(array($relationInfo[2] => $this->{$relationInfo[1]}));
        } else {
            $query = (array) $query;
            if ($relationInfo[2] == '_id' && is_array($this->{$relationInfo[1]})) {
                $query[$relationInfo[2]] = array('$in' => $this->{$relationInfo[1]});
            } else {
                $query[$relationInfo[2]] = $this->{$relationInfo[1]};
            }            
            return $repository->find($query, $sort, $limit, $skip);
        }
    }

    /**
     *
     * @param string $relation the relation name
     * @param Mongo_Model|mixed $related either a Mongo_Model object, a Mongo_Model->_id-value or an array with multiple Mongo_Models
     * @param mixed $save set to null to prevent a save() call, otherwise call save($save)
     * @param bool $multiple true to store multiple related as array (m:n), false to only store a single value (1:1, n:1, default)
     * @return bool
     */
    public function setRelated($relation, $related, $save = true, $multiple = false)
    {
        if (!$relationInfo = static::getRelation($relation)) {
            throw new Exception('Unknown relation "'.$relation.'" for model '.get_class($this));
        }
        if (is_array($related)) {
            foreach ($related as $rel) {
                $this->setRelated($relation, $rel, $save, $multiple);
            }
            return true;
        }
        if (!is_object($related) || !($related instanceof Model)) {
            $repositoryName = $relationInfo[0]::GetRepositoryClass();
            $repository = new $repositoryName($relationInfo[0], $this->getConnection());
            $related = $repository->findOne($related);
            if (!$related) {
                throw new InvalidArgumentException('Could not find valid '.$relationInfo[0]);
            }
        }
        if (!empty($relationInfo[3])) {
            if ($relationInfo[1] == '_id') {
                if (!$this->{$relationInfo[1]}) {
                    if (!static::isAutoId()) {
                        throw new Exception('Counld not set realted '.$relationInfo[0].' - '.$relationInfo[1].' not set!');
                    }
                    $this->{$relationInfo[1]} = new MongoId();
                    if ($save !== null) {
                        $this->save($save);
                    }
                }
                
                $related->{$relationInfo[2]} = $this->{$relationInfo[1]};
                return $save !== null ? $related->save($save) : true;
            } elseif ($relationInfo[2] == '_id') {
                if (!$related->{$relationInfo[2]}) {
                    if (!$relationInfo[0]::isAutoId()) {
                        throw new Exception('Counld not set realted '.$relationInfo[0].' - '.$relationInfo[2].' not set!');
                    }
                    $related->{$relationInfo[2]} = new MongoId();
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
                    throw new Exception('Counld not set realted '.$relationInfo[0].' - '.$relationInfo[1].' not set!');
                }
                $this->{$relationInfo[1]} = new MongoId();
                if ($save !== null) {
                    $this->save($save);
                }
            } elseif ($relationInfo[2] == '_id' && !$related->{$relationInfo[2]}) {
                if (!$relationInfo[0]::isAutoId()) {
                    throw new Exception('Counld not set realted '.$relationInfo[0].' - '.$relationInfo[2].' not set!');
                }
                $related->{$relationInfo[2]} = new MongoId();
                if ($save !== null) {
                    $related->save($save);
                }
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
     * @param Mongo_Model|mixed $related true to remove all objects or either a Mongo_Model object, a Mongo_Model->_id-value  or an array with multiple Mongo_Models
     * @param boolean $delete true to delete the related entry, false to only remove the relation (default false) 
     * @param mixed $save set to null to prevent a save() call, otherwise call save($save)
     * @return bool
     */
    public function removeRelated($relation, $related = true, $delete = false, $save = true)
    {
        if (!$relationInfo = static::getRelation($relation)) {
            throw new Exception('Unknown relation "'.$relation.'" for model '.get_class($this));
        }
        if (is_array($related)) {
            foreach ($related as $rel) {
                $this->removeRelated($relation, $rel, $delete, $save);
            }
            return true;
        }
        if (!empty($relationInfo[3])) {
            
            $repositoryName = $relationInfo[0]::GetRepositoryClass();
            $repository = new $repositoryName($relationInfo[0], $this->getConnection());
                
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
            if ($related === true) {
                if ($relationInfo[2] == '_id') {                    
                    if ($delete) {
                        $repositoryName = $relationInfo[0]::GetRepositoryClass();
                        $repository = new $repositoryName($relationInfo[0], $this->getConnection());
                        
                        $options = $save !== null ? array('safe' => $save) : array();
                        if (is_array($this->{$relationInfo[1]})) {
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
                    $repositoryName = $relationInfo[0]::GetRepositoryClass();
                    $repository = new $repositoryName($relationInfo[0], $this->getConnection());
                    $related = $repository->find(array($relationInfo[2] => $this->{$relationInfo[1]}));
                    foreach ($related as $rel) {
                        if (is_array($related->{$relationInfo[2]})) {
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
                    $repositoryName = $relationInfo[0]::GetRepositoryClass();
                    $repository = new $repositoryName($relationInfo[0], $this->getConnection());
                    $related = $repository->findOne($related);
                }
                if (!$related) {
                    return false;
                }
                if ($related->{$relationInfo[2]} != $this->{$relationInfo[1]} && !is_array($related->{$relationInfo[2]}) && !is_array($this->{$relationInfo[1]})) {
                    return false;
                }
                if ($relationInfo[1] == '_id') {
                    if (is_array($related->{$relationInfo[2]})) {
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
                    if (is_array($this->{$relationInfo[1]})) {
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
     * @param string $embedded the embedded name
     * @param int|bool $key the identifier of a embedded or true to return all
     * @param string $sortBy (optional) if $key == true, order the entries by this property, null to keep the db order
     * @param bool $sortDesc false (default) to sort ascending, true to sort descending
     * @return Mongo_Embedded|array
     */
    public function getEmbedded($embedded, $key = true, $sortBy = null, $sortDesc = false)
    {
        if (!$embeddedInfo = static::getEmbeddeds($embedded)) {
            throw new Exception('Unknown embedded "'.$embedded.'" for model '.get_class($this));
        }
        $className = $embeddedInfo[0];
        if (!empty($embeddedInfo[3])) {            
            return !empty($this->{$embeddedInfo[1]}) ? new $className($this->{$embeddedInfo[1]}) : null;
        } else {
            if ($key !== true) {
                foreach ((array) $this->{$embeddedInfo[1]} as $data) {
                    if (isset($data[$embeddedInfo[2]]) && $data[$embeddedInfo[2]] == $key) {
                        return new $className($data);
                    }
                }
                return null;
            } else {
                $return = array();
                foreach ((array) $this->{$embeddedInfo[1]} as $data) {
                    if (isset($data[$embeddedInfo[2]])) {
                        $return[$data[$embeddedInfo[2]]] = new $className($data);
                    } else {
                        $return[] = new $className($data);
                    }
                }
                if (is_string($sortBy)) {
                    $return = $className->sort($return, $sortBy, (bool) $sortDesc);
                }
                return $return;
            }
        }
    }

    /**
     *
     * @param string $embedded the embedded name
     * @param Mongo_Embedded|array $data an array of Mongo_Embedded objects or an array representing a Mongo_Embedded or an array with multiple Mongo_Embeddeds
     * @param mixed $save set to null to prevent a save() call, otherwise call save($save)
     * @return bool
     */
    public function setEmbedded($embedded, $data, $save = true)
    {
        if (!$embeddedInfo =static::getEmbeddeds($embedded)) {
            throw new Exception('Unknown embedded "'.$embedded.'" for model '.get_class($this));
        }
        $className = $embeddedInfo[0];
        if (!empty($embeddedInfo[3])) {
            if (is_object($data) && $data instanceof Embedded) {
                $this->{$embeddedInfo[1]} = $data->getData();
            } else {
                $this->{$embeddedInfo[1]} = $data;
            }
            if ($save !== null) {
                if ($this->getCollection()->update(array('_id' => $this->_id), array('$set' => array($embeddedInfo[1] => $data)), array('safe' => $save))) {
                    $this->setDatabaseProperty($embeddedInfo[1], $data);
                    return true;
                }
                return false;
            }
            return true;
        } else {
            if (!is_array($data) || !isset($data[0])) {
                $data = array($data);
            }
            $set = array();
            $pushAll = array();
            $currentEntries = (array) $this->{$embeddedInfo[1]};
            foreach ($data as $entry) {
                if (is_object($entry) && $entry instanceof Embedded) {
                    $entry = $entry->getData();
                }
                if (empty($entry[$embeddedInfo[2]])) {
                    $entry[$embeddedInfo[2]] = $this->generateEmbeddedKey($currentEntries, $embeddedInfo[2]);
                    $currentEntries[] = $entry;
                    $pushAll[] = $entry;
                } else {
                    $found = false;
                    foreach ($currentEntries as $key => $value) {
                        if ($value[$embeddedInfo[2]] == $entry[$embeddedInfo[2]]) {
                            $currentEntries[$key] = $value;
                            $set[$key] = $value;
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $currentEntries[] = $entry;
                        $pushAll[] = $entry;
                    }
                }
            }
            $this->{$embeddedInfo[1]} = $currentEntries;
            $query = array();
            if (count($pushAll)) {
                $query['$pushAll'] = array($embeddedInfo[1] => $pushAll);
            }
            if (count($set)) {
                $dbSet = array();
                foreach ($set as $k => $v) {
                    $dbSet[$embeddedInfo[1].'.'.$k] = $v;
                }
                $query['$set'] = $dbSet;
            }
            if ($save !== null) {
                if ($this->getCollection()->update(array('_id' => $this->_id), $query, array('safe' => $save))) {
                    $dbValues = (array) $this->getDatabaseProperty($embeddedInfo[1]);
                    foreach ($pushAll as $entry) {
                        $dbValues[] = $entry;
                    }
                    foreach ($set as $k => $v) {
                        $dbValues[$k] = $v;
                    }
                    $this->setDatabaseProperty($embeddedInfo[1], $dbValues);
                    return true;
                }
                return false;
            }
            return true;
        }
    }

    /**
     * removes the chosen Mongo_Embeddeds (or all for $key = true) from the embedded list
     *
     * @param string $embedded the embedded name
     * @param mixed $key one or more keys for Mongo_Embedded objects or true to remove all
     * @param mixed $save set to null to prevent a save() call, otherwise call save($save)
     * @return bool
     */
    public function removeEmbedded($embedded, $key = true, $save = true)
    {
        if (!$embeddedInfo = static::getEmbeddeds($embedded)) {
            throw new Exception('Unknown embedded "'.$embedded.'" for model '.get_class($this));
        }
        if ($key === true) {
             $this->{$embeddedInfo[1]} = array();
             if ($save !== null) {
                if ($this->getCollection()->update(array('_id' => $this->_id), array('$set' => array($embeddedInfo[1] => array())), array('safe' => $save))) {
                    $this->setDatabaseProperty($embeddedInfo[1], array());
                    return true;
                }
                return false;
            }
            return true;
        } else {
            if (!is_array($key)) {
                $key = array($key);
            }
            $unset = false;
            $currentData = (array) $this->{$embeddedInfo[1]};
            foreach ($key as $entry) {
                foreach ($currentData as $currentKey => $value) {
                    if ($value[$embeddedInfo[2]] == $entry) {
                        $unset=true;
                        unset($currentData[$currentKey]);
                        break;
                    }
                }
            }
            if (!$unset) {
                return true;
            }
            $this->{$embeddedInfo[1]} = array_values($currentData);
            if ($save !== null) {
                return $this->save($save);
            }
            return true;
        }
    }

    protected function generateEmbeddedKey($list, $key)
    {
        $newKey = 1;
        foreach ((array) $list as $current) {
            if ((int) $current[$key] >= $newKey) {
                $newKey = ((int) $current[$key]) + 1;
            }
        }
        return $newKey;
    }

    /**
     *
     * @param bool|integer $safe @see php.net/manual/en/mongocollection.update.php
     * @return bool Returns if the update was successfully sent to the database.
     */
    public function save($safe = true)
    {
        try {
            return $this->getRepository()->save($this, $safe);
        } catch (MongoException $e) {
            throw $e;
            return false;
        }
    }

    /**
     *
     * @param bool|integer $safe @see php.net/manual/en/mongocollection.remove.php
     * @return mixed If "safe" is set, returns an associative array with the status of the remove ("ok"), the number of items removed ("n"), and any error that may have occured ("err"). Otherwise, returns TRUE if the remove was successfully sent, FALSE otherwise.
     */
    public function remove($safe = true)
    {
        try {
            return $this->getRepository()->remove($this, $safe);
        } catch (MongoException $e) {
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
        return static::$columns;
    }
    
    public static function getRelations()
    {
        return static::$relations;
    }
    
    public static function getEmbeddeds()
    {
        return static::$embedded;
    }
    
    public static function getRepositoryClass()
    {
        return static::$repositoryClass;
    }
}