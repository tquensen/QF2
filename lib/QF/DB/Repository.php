<?php
namespace QF\DB;

class Repository
{
    /**
     *
     * @var \PDO
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
        if (!is_subclass_of($this->getEntityClass(), '\\QF\\DB\\Entity')) {
            throw new \InvalidArgumentException('$entityClass must be an \\QF\\DB\\Entity instance or classname');
        }
    }
    
    public function getEntityClass()
    {
        return $this->entityClass ?: static::$defaultEntityClass;
    }
    
    /**
     *
     * @return \PDO
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
     * @param array $data initial data for the model
     * @param bool $isNew whether this is a new object (true, default) or loaded from database (false)
     * @param string $entityClass the class to create or null for the current Repositories entity class
     * @return Entity
     */
    public function create($data = array(), $isNew = true, $entityClass = null)
    {
        if ($entityClass === null) {
            $entityClass = $this->getEntityClass();
        }
        $entity = new $entityClass($this->getDB());
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
    
    public function save(Entity $entity)
	{
        $entityClass = get_class($entity);
        try
        {
            if ($entity->preSave($this->getDB()) === false) {
                return false;
            }
            if ($entity->isNew()) {

                $fields = array();
                $values = array();
                foreach ($entityClass::getColumns() as $column)
                {
                    if (isset($entity->$column) && $entity->$column !== null)
                    {
                        $fields[] = $column;
                        //$query->set(' '.$column.' = ? ');
                        $values[] = $entity->$column;
                    }
                }

                $query = $this->getDB()->prepare('INSERT INTO '.$entityClass::getTablename().' ('.implode(',',$fields).') VALUES ('.implode(',', array_fill(0, count($fields), '?')).')');

                $result = $query->execute($values);

                if ($entityClass::isAutoIncrement())
                {
                    $entity->{$entityClass::getIdentifier()} = $this->getDB()->lastInsertId();
                }

                foreach ($entityClass::getColumns() as $column)
                {
                    $entity->setDatabaseProperty($column, $entity->$column);
                } 

            } else {
                $update = false;
                
                $fields = array();
                $values = array();
                foreach ($entityClass::getColumns() as $column)
                {
                    if ($entity->$column !== $entity->getDatabaseProperty($column))
                    {
                        $fields[] = $column;
                        //$query->set(' '.$column.' = ? ');
                        $values[] = $entity->$column;
                        $update = true;
                    }
                }

                $query = $this->getDB()->prepare('UPDATE '.$entityClass::getTableName().' SET '.implode('=?, ',$fields).'=? WHERE '.$entityClass::getIdentifier().' = ?');

                if (!$update) {
                    return true;
                }

                $values[] = $entity->{$entityClass::getIdentifier()};

                $result = $query->execute($values);

                foreach ($entityClass::getColumns() as $column)
                {
                    if (isset($entity->$column) && $entity->$column !== $entity->getDatabaseProperty($column))
                    {
                        $entity->setDatabaseProperty($column, $entity->$column);
                    }
                }
            }
            
        } catch (Exception $e) {
            throw $e;
        }

		return (bool) $result;
	}
    
    public function remove($entity)
	{
        try
        {
            if (is_object($entity))
            {
                if ($entity->preRemove($this->getDB()) === false) {
                    return false;
                }
            
                $entityClass = get_class($entity);
                if (!isset($entity->{$entityClass::getIdentifier()}) || !$entity->{$entityClass::getIdentifier()})
                {
                    return false;
                }

                $query = $this->getDB()->prepare('DELETE FROM '.$entityClass::getTableName().' WHERE '.$entityClass::getIdentifier().' = ? LIMIT 1');
                $result = $query->execute(array($entity->{$entityClass::getIdentifier()}));
                $entity->clearDatabaseProperties();
            }
            else
            {
                $entityClass = $this->entityClass;
                $query = $this->getDB()->prepare('DELETE FROM '.$entityClass::getTableName().' WHERE '.$entityClass::getIdentifier().' = ? LIMIT 1');
                $result = $query->execute($entity);
            }
            foreach ($entityClass::getRelations() as $relation => $info) {
                if (isset($info[3]) && $info[3] !== true) {
                    $query = $this->getDB()->prepare('DELETE FROM '.$info[3].' WHERE '.$info[1].' = ?');
                    $query->execute(array(is_object($entity) ? $entity->{$entityClass::getIdentifier()} : $entity));
                }
            }

        } catch (Exception $e) {
            throw $e;
        }
		return $result;
	}
    
    public function removeBy($conditions, $values, $cleanRefTable = false)
	{
        $entityClass = $this->getEntityClass();
        $query = 'DELETE FROM '.$entityClass::getTableName();
                
        $where = array();
        foreach ((array) $conditions as $k => $v) {
            if (is_numeric($k)) {
                $where[] = ' '.$v;
            } else {
                $where[] = ' '.$k.'='.$this->getDB()->quote($v);
            }
        }
        if ($where) {
            $query .= ' WHERE'.implode(' AND ', $where);
        }
        $stmt = $this->getDB()->prepare($query);
        $result = $stmt->execute($values);

        if ($cleanRefTable) {
            $this->cleanRefTables();
        }
        
		return $result;
	}
    
    /**
     * deletes all rows in m:n ref tables which have no related entry in this class
     */
    public function cleanRefTables()
    {
        $entityClass = $this->getEntityClass();
        foreach ($entityClass::getRelations() as $relation => $info) {
            if (!isset($info[3]) || $info[3] === true) {
                continue;
            }
            $stmt = $this->getDB()->prepare('SELECT a_b.'.$info[1].' rel1, a_b.'.$info[2].' rel2 FROM '.$info[3].' a_b LEFT JOIN '.$entityClass::getTableName().' a ON a_b.'.$info[1].' = a.'.$entityClass::getIdentifier().' WHERE a.'.$entityClass::getIdentifier().' IS NULL')->execute();
            $refTableIds = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $where = array();
            $values = array();
            foreach ($refTableIds as $row) {
                $where[] = '('.$info[1].' = ? AND '.$info[2].' = ?)';
                $values[] = $row['rel1'];
                $values[] = $row['rel2'];
            }
            $deleteStmt = $this->getDB()->prepare('DELETE FROM '.$info[3].' WHERE '.implode(' OR ', $where))->execute($values);
        }
    }
    
    /**
     *
     * @param array|string $conditions the where conditions
     * @param array $values values for ?-placeholders in the conditions
     * @param string $order an order by clause (id ASC, foo DESC)
     * @return Entity
     */
    public function loadOne($conditions = array(), $values = array(), $order = null)
    {
        $entities = $this->load($conditions, $values, $order, 1, 0, true);
        return reset($entities);
    }
    
    /**
     *
     * @param array|string $conditions the where conditions
     * @param array $values values for ?-placeholders in the conditions
     * @param string $order an order by clause (id ASC, foo DESC)
     * @param int $limit
     * @param int $offset
     * @param bool $build true to build the entities, false to return the statement
     * @return array|\PDOStatement
     */
    public function load($conditions = array(), $values = array(), $order = null, $limit = null, $offset = null, $build = true)
    {
        $entity = $this->getEntityClass();

        if (!is_subclass_of($entity, '\\QF\\DB\\Entity')) {
            throw new \InvalidArgumentException('$entity must be an \\QF\\DB\\Entity instance or classname');
        }
        $query = 'SELECT '.implode(', ', $entity::getColumns()).' FROM '.$entity::getTableName();
        $where = array();
        foreach ((array) $conditions as $k => $v) {
            if (is_numeric($k)) {
                $where[] = ' '.$v;
            } else {
                $where[] = ' '.$k.'='.$this->getDB()->quote($v);
            }
        }
        if ($where) {
            $query .= ' WHERE'.implode(' AND ', $where);
        }
        if ($order) {
            $query .= ' ORDER BY '.$order;
        }
        if ($limit || $offset) {
            $query .= ' LIMIT '.(int)$limit.((int)$offset ? ' OFFSET '.(int)$offset : '');
        }
        $stmt = $this->getDB()->prepare($query);
        $stmt->execute(array_values((array) $values));
        
        if ($build) {
            return $this->build($stmt, $entity);
        } else {
            return $stmt;
        }     
    }
    
    /**
     * $relations is an array of arrays as followed:
     *  array(fromAlias, relationProperty, toAlias, options = array())
     *      options is an array with the following optional keys:
     *          'conditions' => array|string additional where conditions to filter for
     *          'values' => array values for ?-placeholders in the conditions
     *          'order' => string the order of the related entries
     *          'count' => false|string fetch only the number of related entries, not the entries themself
     * 
     *      if count is set, the count of related entities will be saved in the property of the from-object defined by count
     *      (example: 'count' => 'fooCount' will save the number of related entries in $fromObject->fooCount)
     * 
     * @param array $relations the relations
     * @param array|string $conditions the where conditions
     * @param array $values values for ?-placeholders in the conditions
     * @param string $order an order by clause (id ASC, foo DESC)
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function loadWithRelations($relations = array(), $conditions = array(), $values = array(), $order = null, $limit = null, $offset = null)
    {
        $entityClasses = array();
        $entityClasses['a'] = $this->getEntityClass();

        if (!is_subclass_of($entityClasses['a'], '\\QF\\DB\\Entity')) {
            throw new \InvalidArgumentException('$entity must be an \\QF\\DB\\Entity instance or classname');
        }
        
        $query = 'SELECT '.implode(', ', $entityClasses['a']::getColumns()).' FROM '.$entityClasses['a']::getTableName();
        $where = array();
        foreach ((array) $conditions as $k => $v) {
            if (is_numeric($k)) {
                $where[] = ' '.$v;
            } else {
                $where[] = ' '.$k.'='.$this->getDB()->quote($v);
            }
        }
        if ($where) {
            $query .= ' WHERE'.implode(' AND ', $where);
        }
        if ($order) {
            $query .= ' ORDER BY '.$order;
        }
        if ($limit || $offset) {
            $query .= ' LIMIT '.(int)$limit.((int)$offset ? ' OFFSET '.(int)$offset : '');
        }
        $stmt = $this->getDB()->prepare($query);
        $stmt->execute(array_values((array) $values));
        
        $entities = array();
        $entities['a'] = $this->build($stmt, $entity);
        
        foreach ($relations as $rel) {
            if (empty($rel[0]) || !isset($entityClasses[$rel[0]])) {
                throw new \Exception('unknown fromAlias '.$rel[0]);
            }
            if (empty($rel[2])) {
                throw new \Exception('missing toAlias');
            }
            
            if (!is_subclass_of($entityClasses[$rel[0]], '\\QF\\DB\\Entity')) {
                throw new \InvalidArgumentException('$entity must be an \\QF\\DB\\Entity instance or classname');
            }
            
            if (empty($rel[1]) || !$relData = $entityClasses[$rel[0]]::getRelation($rel[1])) {
                throw new \Exception('unknown relation '.$rel[0].'.'.$rel[1]);
            }
            
            $entityClasses[$rel[2]] = $relData[0];
            
            $options = !empty($rel[3]) ? (array) $rel[3] : array();
            $values = (array) (!empty($rel[4]) ? $rel[4] : null);
            $condition = (array) (!empty($rel[3]) ? $rel[3] : null);
                
            $repository = $relData[0]::getRepository($this->getDB());
            
            
            if (!empty($options['count'])) {
                foreach ($entities[$rel[0]] as $fromEntity) {
                    $fromEntity->countRelated($rel[1], $condition, $values, $options['count']);
                }
                continue;
            }
            
            if (isset($relData[3]) && $relData[3] !== true) {
                foreach ($entities[$rel[0]] as $fromEntity) {
                    array_push($values, $fromEntity->{static::getIdentifier()});
                }
                $stmt = $this->getDB()->prepare('SELECT '.$data[1].' a, '.$data[2].' b FROM '.$data[3].' WHERE '.$data[1].' IN ('.implode(',', array_fill(0, count($entities['a']), '?')).')')
                        ->execute($values);
                $refTableIds = $stmt->fetchAll(PDO::FETCH_ASSOC);
             
                foreach ($refTableIds as $row) {
                    array_push($values, $row['b']);
                }
                
                array_push($condition, $relData[0]::getIdentifier().' IN ('.implode(',', array_fill(0, count($refTableIds), '?')).')');
                
                $entities[$rel[2]] = $repository->load($condition, $values, !empty($options['order']) ? $options['order'] : null);
                
                foreach ($refTableIds as $row) {
                    $entities[$rel[0]][$row['a']]->add($rel[1], $entities[$rel[2]][$row['b']]);
                }
                
            } else {                
                foreach ($entities[$rel[0]] as $fromEntity) {
                    array_push($values, $fromEntity->{$relData[1]});
                }
                
                array_push($condition, $relData[2].' IN ('.implode(',', array_fill(0, count($entities['a']), '?')).')');

                $entities[$rel[2]] = $repository->load($condition, $values, !empty($options['order']) ? $options['order'] : null);
                
                foreach ($entities[$rel[0]] as $fromEntity) {
                    foreach ($entities[$rel[3]] as $toEntity) {
                        if ($fromEntity->{$relData[1]} == $toEntity->{$relData[2]}) {
                            if (!empty($relData[3])) {
                                $fromEntity->set($rel[1], $toEntity);
                            } else {
                                $fromEntity->add($rel[1], $toEntity);
                            }
                        }
                    }
                }
            }
        }
        
        return $entities['a'];
        
    }
    
    /**
     *
     * @param array|string $conditions the where conditions
     * @param array $values values for ?-placeholders in the conditions
     * @return int
     */
    public function count($conditions = array(), $values = array())
    {
        $entity = $this->getEntityClass();

        if (!is_subclass_of($entity, '\\QF\\DB\\Entity')) {
            throw new \InvalidArgumentException('$entity must be an \\QF\\DB\\Entity instance or classname');
        }
        $query = 'SELECT count('.$entity::getIdentifier().') FROM '.$entity::getTableName();
        $where = array();
        foreach ((array) $conditions as $k => $v) {
            if (is_numeric($k)) {
                $where[] = ' '.$v;
            } else {
                $where[] = ' '.$k.'='.$this->getDB()->quote($v);
            }
        }
        if ($where) {
            $query .= ' WHERE'.implode(' AND ', $where);
        }
        
        $stmt = $this->getDB()->prepare($query);
        $stmt->execute(array_values((array) $values));
        
        $count = $stmt->fetchColumn();
        $stmt->closeCursor();
           
        return $count;
    }
    
    
    
    /**
     * builds entities from a PDOStatement
     * 
     * @param \PDOStatement $statement the pdo statement
     * @param mixed $entityClass the entity class to use
     * @return array the resulting entities 
     */
    public function build(\PDOStatement $statement, $entityClass = null)
    {
        if ($entityClass === null) {
            $entityClass = $this->getEntityClass();
        }

        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $returnData = array();
        $key = $entityClass::getIdentifier();
        foreach ($results as $row) {
            $entity = $this->create($row, false, $entityClass);
            $returnData[$entity->$key] = $entity;
        }
        
        return $returnData; 
    }
    
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
