<?php
namespace Mongo;

abstract class Entity extends \QF\Entity
{
    protected $_databaseProperties = array();
    protected $_connection = null;
    
    protected static $collectionName = null;
    protected static $autoId = false;
    protected static $columns = array();
    protected static $relations = array();
    protected static $repositoryClass = '\\Mongo\\Repository';

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
                    return $this->loadRelated($property, isset($args[0]) ? $args[0] : array(), isset($args[1]) ? $args[1] : array(), isset($args[2]) ? $args[2] : null, isset($args[3]) ? $args[3] : null);
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
        $this->set($property, $this->get($property) + $value);
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
    public function loadRelated($relation, $query = array(), $sort = array(), $limit = null, $skip = null)
    {
        if (!$relationInfo = static::getRelation($relation)) {
            throw new Exception('Unknown relation "'.$relation.'" for model '.get_class($this));
        }
        $repositoryName = $relationInfo[0]::GetRepositoryClass();
        $repository = new $repositoryName($this->getConnection(), $relationInfo[0]);
            
        if (!empty($relationInfo[3])) {
            $related = $repository->findOne(array($relationInfo[2] => $this->{$relationInfo[1]}));
            $this->set($relation, $related);
            return $related;
        } else {
            $query = (array) $query;
            if ($relationInfo[2] == '_id' && is_array($this->{$relationInfo[1]})) {
                $query[$relationInfo[2]] = array('$in' => $this->{$relationInfo[1]});
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
     * @param Mongo_Model|mixed $related either a Mongo\Model object, a Mongo\Model->_id-value or an array with multiple Mongo\Models
     * @param mixed $save set to null to prevent a save() call, otherwise call save($save)
     * @param bool $multiple true to store multiple related as array (m:n), false to only store a single value (1:1, n:1, default)
     * @return bool
     */
    public function linkRelated($relation, $related, $save = true, $multiple = false)
    {
        if (!$relationInfo = static::getRelation($relation)) {
            throw new Exception('Unknown relation "'.$relation.'" for model '.get_class($this));
        }
        if (is_array($related)) {
            foreach ($related as $rel) {
                $this->linkRelated($relation, $rel, $save, $multiple);
            }
            return true;
        }
        if (!is_object($related) || !($related instanceof Model)) {
            $repositoryName = $relationInfo[0]::GetRepositoryClass();
            $repository = new $repositoryName($this->getConnection(), $relationInfo[0]);
            $related = $repository->findOne($related);
            if (!$related) {
                throw new InvalidArgumentException('Could not find valid '.$relationInfo[0]);
            }
        }
        if (!empty($relationInfo[3])) {
            if ($relationInfo[1] == '_id') {
                if (!$this->{$relationInfo[1]}) {
                    if (!static::isAutoId()) {
                        throw new Exception('Counld not link realted '.$relationInfo[0].' - '.$relationInfo[1].' not set!');
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
                        throw new Exception('Counld not link realted '.$relationInfo[0].' - '.$relationInfo[2].' not set!');
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
                    throw new Exception('Couldnt not link realted '.$relationInfo[0].' - '.$relationInfo[1].' not set!');
                }
                $this->{$relationInfo[1]} = new MongoId();
                if ($save !== null) {
                    $this->save($save);
                }
            } elseif ($relationInfo[2] == '_id' && !$related->{$relationInfo[2]}) {
                if (!$relationInfo[0]::isAutoId()) {
                    throw new Exception('Couldnt not link realted '.$relationInfo[0].' - '.$relationInfo[2].' not set!');
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
     * @param \Mongo\Entity|mixed $related true to unlink all objects or either a \Mongo\Entity object, a \Mongo\Entity->_id-value  or an array with multiple \Mongo\Entity
     * @param boolean $delete true to delete the related entry, false to only remove the relation (default false) 
     * @param mixed $save set to null to prevent a save() call, otherwise call save($save)
     * @return bool
     */
    public function unlinkRelated($relation, $related = true, $delete = false, $save = true)
    {
        if (!$relationInfo = static::getRelation($relation)) {
            throw new Exception('Unknown relation "'.$relation.'" for model '.get_class($this));
        }
        if (is_array($related)) {
            foreach ($related as $rel) {
                $this->unlinkRelated($relation, $rel, $delete, $save);
            }
            return true;
        }
        if (!empty($relationInfo[3])) {
            
            $repositoryName = $relationInfo[0]::GetRepositoryClass();
            $repository = new $repositoryName($this->getConnection(), $relationInfo[0]);
                
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
                        $repository = new $repositoryName($this->getConnection(), $relationInfo[0]);
                        
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
                    $repository = new $repositoryName($this->getConnection(), $relationInfo[0]);
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
                    $repository = new $repositoryName($this->getConnection(), $relationInfo[0]);
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
     * @param bool|integer $safe @see php.net/manual/en/mongocollection.update.php
     * @return bool Returns if the update was successfully sent to the database.
     */
    public function save($safe = true)
    {
        try {
            return $this->getRepository()->save($this, $safe);
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
    public function delete($safe = true)
    {
        try {
            return $this->getRepository()->remove($this, $safe);
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
    
    public static function getRepositoryClass()
    {
        return static::$repositoryClass;
    }
}