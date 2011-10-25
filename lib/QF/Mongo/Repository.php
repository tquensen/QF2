<?php
namespace QF\Mongo;

class Repository
{

    /**
     *
     * @var \MongoDB
     */
    protected $db = null;
    
    protected $entityClass = null;
    
    protected static $defaultEntityClass = null;

    public function __construct($db, $entityClass = null)
    {
        $this->db = $db;

        if ($entityClass) { 
            if (is_object($entityClass)) {
                $entityClass = get_class($entityClass);
            }
            
            $this->entityClass = $entityClass;
        }
        if (!is_subclass_of($this->getEntityClass(), '\\Mongo\\Entity')) {
            throw new \InvalidArgumentException('$entityClass must be an \\Mongo\\Entity instance or classname');
        }
    }
    
    public function getEntityClass()
    {
        return $this->entityClass ?: static::$defaultEntityClass;
    }

    /**
     *
     * @return MongoDB
     */
    public function getDB()
    {
        return $this->db;
    }

    public function setDB($db)
    {
        $this->db = $db;
    }
    
    /**
     *
     * @return \MongoCollection
     */
    public function getCollection()
    {
        $collectionName = $this->getCollectionName();
        return $this->getDB()->$collectionName;
    }
    
    /**
     *
     * @return string
     */
    public function getCollectionName()
    {
        $entityClass = $this->getEntityClass();
        return $entityClass::getCollectionName();
    }

    /**
     *
     * @param array $data initial data for the model
     * @param bool $isNew whether this is a new object (true, default) or loaded from database (false)
     * @return Entity
     */
    public function create($data = array(), $isNew = true)
    {
        $name = $this->getEntityClass();
        $entity = new $name($this->getDB());
        if ($isNew) {
            foreach ($data as $k => $v) {
                $entity->$k = $v;
            }
            $entity->postCreate();
        } else {
            foreach ($data as $k => $v) {
                $entity->$k = $v;
                $entity->setDatabaseProperty($k, $v);
            }
            $entity->postLoad();
        }
        return $entity;
    }

    /**
     *
     * @param \MongoCursor|array $data initial data for the models
     * @param bool $single set to true if $data is a single model
     * @return array
     */
    public function build($data = array(), $single = false)
    {
        $return = array();
        if ($single) {
            $data = array($data);
        }
        
        $entityClass = $this->getEntityClass();
        
        foreach ($data as $current) {
            $model = $this->create($current, false);
            if (is_array($model->_id)) {
                $return[] = $model;
            } else {
                $return[(string) $model->_id] = $model;
            }
        }
        return $single ? reset($return) : $return;
    }

    /**
     *
     * @param mixed $query an array of fields for which to search or the _id as string or MongoId
     * @param bool $build (default true) set to false to return the raw MongoCursor
     * @return Entity the matching record or null
     */
    public function findOne($query, $build = true)
    {
        $entityClass = $this->getEntityClass();
        
        if (is_string($query)) {
            $query = array('_id' => $entityClass::isAutoId() ? new \MongoId($query) : $query);
        } elseif ($query instanceof \MongoId) {
            $query = array('_id' => $query);
        }
        $data = $this->getCollection()->findOne($query);
        if ($data) {
            return $this->build($data, true);
        }
    }

    /**
     *
     * @param array $query Associative array or object with fields to match.
     * @param int $limit Specifies an upper limit to the number returned.
     * @param int $skip Specifies a number of results to skip before starting the count.
     * @return int returns the number of documents matching the query.
     */
    public function count($query = array(), $limit = null, $skip = null)
    {
        return $this->getCollection()->count($query, $limit, $skip);
    }

    /**
     *
     * @param array $sort The fields by which to sort.
     * @param bool $build (default true) set to false to return the raw MongoCursor
     * @return \MongoCursor|array Returns an array or a cursor for the search results.
     */
    public function findAll($sort = array(), $build = true)
    {
        return $this->find(array(), array(), $sort, null, null, $build);
    }

    /**
     *
     * @param array $query The fields for which to search.
     * @param array $sort The fields by which to sort.
     * @param int $limit The number of results to return.
     * @param int $skip The number of results to skip.
     * @param bool $build (default true) set to false to return the raw MongoCursor
     * @return \MongoCursor|array Returns an array or a cursor for the search results.
     */
    public function find($query = array(), $sort = array(), $limit = null, $skip = null, $build = true)
    {
        $cursor = $query ? $this->getCollection()->find($query) : $this->getCollection()->find();
        if ($sort) {
            $cursor->sort($sort);
        }
        if ($limit) {
            $cursor->limit($limit);
        }
        if ($skip) {
            $cursor->skip($skip);
        }

        return $build ? $this->build($cursor) : $cursor;
    }
    
    /**
     * creates a Mongo\MapReduce object for this collection
     * 
     * @param MongoCode|string $map the map function as MongoCode or string
     * @param MongoCode|string $reduce the reduce function as MongoCode or string
     * @param MongoCode|string $finalize the finalize function as MongoCode or string
     * @return MapReduce 
     */
    public function mapReduce($map = null, $reduce = null, $finalize = null)
    {
        return new MapReduce($this->getCollectionName(), $this->getDB(), $map, $reduce, $finalize);
    }
    
    /**
     *
     * @param array $query The fields for which to filter.
     * @param bool $justOne Remove at most one record matching this criteria.
     * @param bool|integer $safe @see php.net/manual/en/mongocollection.remove.php
     * @param bool $raw true to remove the entries directly, false (default) to call remove() on each model 
     * @return bool if $raw is true, returns the status of the query. If $raw is false, it returns always true
     */
    public function removeBy($query = array(), $justOne = false, $safe = true, $raw = false)
    {
        if ($raw) {
            $options = array();
            if ($justOne) {
                $options['justOne'] = true;
            }
            if ($safe) {
                $options['safe'] = $safe;
            }
            return $this->getCollection()->remove($query);
        } else {
            foreach($this->find($query, array(), $justOne ? 1 : null, null) as $model) {
                $model->remove($safe);
            } 
            return true;
        }
    }

    /**
     *
     * @param Entity $entity the model to save
     * @param bool|integer $safe @see php.net/manual/en/mongocollection.update.php
     * @return bool returns if the update was successfully sent to the database.
     */
    public function save(Entity $entity, $safe = true)
    {
        $entityClass = get_class($entity);
        
        try {
            if ($entity->preSave($this->getDB()) === false) {
                return false;
            }

            if ($entity->isNew()) {
                $insert = array();
                foreach ($entityClass::getColumns() as $column) {
                    if ($entity->$column !== null) {
                        $insert[$column] = $entity->$column;
                    }
                }
                $status = $this->getCollection()->insert($insert, array('safe' => $safe));
                if ($status) {
                    if ($entityClass::isAutoId()) {
                        $entity->_id = $insert['_id'];
                    }
                    foreach ($insert as $key => $value) {
                        $entity->setDatabaseProperty($key, $value);
                    }
                    return true;
                }
                return false;
            } else {
                $query = array();
                foreach ($entityClass::getColumns() as $column) {
                    if ($entity->$column !== $entity->getDatabaseProperty($column)) {
                        if ($entity->$column === null) {
                            $query['$unset'][$column] = 1;
                        } else {
                            $query['$set'][$column] = $entity->$column;
                        }
                    }
                }
                if (!count($query)) {
                    return true;
                }
                $status = $this->getCollection()->update(array('_id' => $entity->_id), $query, array('safe' => $safe));
                if ($status) {
                    if (!empty($query['$set'])) {
                        foreach ($query['$set'] as $key => $value) {
                            $entity->setDatabaseProperty($key, $value);
                        }
                    }
                    if (!empty($query['$unset'])) {
                        foreach ($query['$unset'] as $key => $dummy) {
                            $entity->setDatabaseProperty($key, null);
                        }
                    }
                    return true;
                }
                return false;
            }
        } catch (\Exception $e) {
            //var_dump($e->getMessage());
            throw $e;
        }
    }

    /**
     *
     * @param Entity $entity the model to remove
     * @param bool|integer $safe @see php.net/manual/en/mongocollection.remove.php
     * @return mixed If "safe" is set, returns an associative array with the status of the remove ("ok"), the number of items removed ("n"), and any error that may have occured ("err"). Otherwise, returns TRUE if the remove was successfully sent, FALSE otherwise.
     */
    public function remove(Entity $entity, $safe = true)
    {
        if (!$entity->_id) {
            return false;
        }
        try {
            if ($entity->preRemove($this->getDB()) === false) {
                return false;
            }
            $status = $this->getCollection()->remove(array('_id' => $entity->_id), array('safe' => $safe !== null ? $safe : false));
            if ($status) {
                $entity->clearDatabaseProperties();
                return true;
            }
            return false;
        } catch (Exception $e) {
            throw $e;
        }
    }

//    public function generateSlug($entry, $source, $field, $maxlength = 255)
//    {
//        $baseslug = MiniMVC_Registry::getInstance()->helper->text->sanitize($source, true);
//        $id = $entry->_id;
//        $num = 0;
//        $slug = $baseslug;
//        do {
//            if (mb_strlen($slug, 'UTF-8') > $maxlength) {
//                $baseslug = mb_substr($baseslug, 0, $maxlength - strlen((string) $num), 'UTF-8');
//                $slug = $baseslug . $num;
//            }
//
//            $result = $this->getCollection()->findOne(array($field => $slug), array('_id'));
//            $num--;
//        } while($result && (string)$result['_id'] != (string)$id && $slug = $baseslug . $num);
//        return $slug;
//    }

    /**
     * @param string $sb the database connection to use
     * @param string|Entity $entityClass
     * @return Mongo_Repository
     */
    public static function get($db, $entityClass = null)
    {
        if (!$entityClass) { 
            $entityClass = static::$defaultEntityClass;
        } elseif (is_object($entityClass)) {
            $entityClass = get_class($entityClass);
        }           
        return $entityClass::getRepository($db);
    }

}