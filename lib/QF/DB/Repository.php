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
        } else {
            foreach ($data as $k => $v) {
                $entity->$k = $v;
                $entity->setDatabaseProperty($k, $v);
            }
            $entity->postLoad($this->getDB());
        }
        return $entity;
    }
    
    public function save(Entity $entity)
	{
        $entityClass = get_class($entity);
        try
        {
            if ($entity->preSave($this->getDB(), !$this->isNew()) === false) {
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
                
                if ($result) {
                    $entity->postSave($this->getDB(), false);
                }
                
                return (bool) $result;

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
                
                if ($result) {
                    $entity->postSave($this->getDB(), true);
                }
                
                return (bool) $result;
            }
            
        } catch (Exception $e) {
            throw $e;
        }

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
                
                if ($result) {
                    $entity->postRemove($this->getDB());
                }
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
    
    public function removeBy($conditions, $values, $raw = false, $cleanRefTable = false)
	{
        if ($raw) {
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
        } else {
            foreach($this->load($conditions, $values) as $entity) {
                $entity->delete();
            } 
            return true;
        }
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
     *      the fromAlias of the initial entity is 'a'
     * 
     *      if count is set, the count of related entities will be saved in the property of the from-object defined by count
     *      (example: 'count' => 'fooCount' will save the number of related entries in $fromObject->fooCount)
     * 
     * @param array $relations the relations
     * @param array|string $conditions the where conditions
     * @param array $values values for ?-placeholders in the conditions
     * @param string $order an order by clause (id ASC, foo DESC)
     * @return mixed the first entity found or false
     */
    public function loadOneWithRelations($relations = array(), $conditions = array(), $values = array(), $order = null)
    {
        $results = $this->loadWithRelations($relations, $conditions, $values, $order, 1);
        return reset($results);
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
     *      the fromAlias of the initial entity is 'a'
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
        $entities['a'] = $this->build($stmt, $entityClasses['a']);
        
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
                
                array_push($condition, $relData[2].' IN ('.implode(',', array_fill(0, count($entities[$rel[0]]), '?')).')');

                $entities[$rel[2]] = $repository->load($condition, $values, !empty($options['order']) ? $options['order'] : null);
                
                foreach ($entities[$rel[0]] as $fromEntity) {
                    foreach ($entities[$rel[2]] as $toEntity) {
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
   
    /**
     * alternative method to load entities with relations (this method uses one Query with JOINS)
     *      differences to loadWithRelations:
     *      - performance may vary based on number of fetched relations
     *      - cross-table conditions (e.g. a.foo = b.bar)
     *      - option to select certain referenced tables
     *      - no ORDER BY for related entities
     *      - the prefix/alias must be used in any conditions
     * 
     * $relations is an array of arrays as followed:
     *  array(fromAlias, relationProperty, toAlias, options = array())
     *      options is an array with the following optional keys:
     *          'select' => true|false if the entites should be selected
     *          'conditions' => array|string additional where conditions to filter for
     *          'values' => array values for ?-placeholders in the conditions
     *          'count' => false|string fetch only the number of related entries, not the entries themself
     * 
     *      the fromAlias of the initial entity is 'a'
     * 
     *      if count is set, the count of related entities will be saved in the property of the from-object defined by count
     *      (example: 'count' => 'fooCount' will save the number of related entries in $fromObject->fooCount)
     * 
     * @param array $relations the relations
     * @param array|string $conditions the where conditions
     * @param array $values values for ?-placeholders in the conditions
     * @param string $order an order by clause (id ASC, foo DESC)
     * @return mixed the first entity found or false
     */
    public function loadOneWithRelationsAlt($relations = array(), $conditions = array(), $values = array(), $order = null)
    {
        $results = $this->loadWithRelationsAlt($relations, $conditions, $values, $order, 1);
        return reset($results);
    }
    
    /**
     * alternative method to load entities with relations (this method uses one Query with JOINS)
     *      differences to loadWithRelations:
     *      - performance may vary based on number of fetched relations
     *      - cross-table conditions (e.g. a.foo = b.bar)
     *      - option to select certain referenced tables
     *      - no ORDER BY for related entities
     *      - the prefix/alias must be used in any conditions
     * 
     * $relations is an array of arrays as followed:
     *  array(fromAlias, relationProperty, toAlias, options = array())
     *      options is an array with the following optional keys:
     *          'select' => true|false if the entites should be selected
     *          'conditions' => array|string additional where conditions to filter for
     *          'values' => array values for ?-placeholders in the conditions
     *          'count' => false|string fetch only the number of related entries, not the entries themself
     * 
     *      the fromAlias of the initial entity is 'a'
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
     * @return array the resulting entities 
     */
    public function loadWithRelationsAlt($relations = array(), $conditions = array(), $values = array(), $order = null, $limit = null, $offset = null)
    {
        $entityClasses = array();
        $entityIdentifiers = array();
        $entityRepositories = array();
        $entityClasses['a'] = $this->getEntityClass();
        
        if (!is_subclass_of($entityClasses['a'], '\\QF\\DB\\Entity')) {
            throw new \InvalidArgumentException('$entity must be an \\QF\\DB\\Entity instance or classname');
        }
        
        $entityIdentifiers['a'] = $entityClasses['a']::getIdentifier();
        $entityRepositories['a'] = $entityClasses['a']::getRepository($this->getDB());
        
        $placeholders = array();
        $query = 'SELECT '.implode(', ', $entityClasses['a']::getColumns('a'));
        
        $needPreQuery = false;
        
        foreach ($relations as $k => $rel) {
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
                $entityIdentifiers[$rel[2]] = $entityClasses[$rel[2]]::getIdentifier();
                $entityRepositories[$rel[2]] = $entityClasses[$rel[2]]::getRepository($this->getDB());
                
                $relations[$k][4] = $entityClasses[$rel[2]]::getRelation($rel[1]);
                
                if (!empty($rel[3]['select'])) {
                    $query .= ', '.implode(', ', $entityClasses[$rel[2]]::getColumns($rel[2]));
                } elseif (!empty($rel[3]['count'])) {
                    $query .= ', COUNT(DISTICT('.$rel[2].'.'.$entityClasses[$rel[2]]::getIdentifier().')) '.$rel[0].'_'.$rel[3]['count'];
                }
        }
        
        $query .= ' FROM '.$entityClasses['a']::getTableName().' a ';
        
        $relQuery = array();
        foreach ($relations as $rel) {
            $currentRelQuery = '';
            if (!empty($rel[4][3]) && $rel[4][3] !== true) {
                $needPreQuery = true;
                $currentRelQuery .= ' LEFT JOIN '.$rel[4][3].' '.$rel[0].'_'.$rel[2].' ON '.$rel[0].'.'.$entityClasses[$rel[0]]::getIdentifier().' = '.$rel[0].'_'.$rel[2].'.'.$rel[4][1].' LEFT JOIN '.$entityClasses[$rel[2]]::getTableName().' '.$rel[2].' ON '.$rel[0].'_'.$rel[2].'.'.$rel[4][2].' = '.$entityClasses[$rel[2]]::getIdentifier();
            } else {
                if (empty($rel[4][3])) {
                    $needPreQuery = true;
                }
                $currentRelQuery .= ' LEFT JOIN '.$entityClasses[$rel[2]]::getTableName().' '.$rel[2].' ON '.$rel[0].'.'.$rel[4][1].' = '.$rel[2].'.'.$rel[4][2];
            }
            if (!empty($rel[3]['conditions'])) {
                $where = array();
                foreach ((array) $rel[3]['conditions'] as $k => $v) {
                    if (is_numeric($k)) {
                        $where[] = ' '.$v;
                    } else {
                        $where[] = ' '.$k.'='.$this->getDB()->quote($v);
                    }
                }
                if ($where) {
                    $currentRelQuery .= ' AND'.implode(' AND ', $where);
                }
            }
            if (!empty($rel[3]['values'])) {
                $placeholders = array_merge($placeholders, (array) $rel[3]['values']);
            }
            $relQuery[] = $currentRelQuery;
        }
        
        $query .= implode(' ', $relQuery);
        
        $where = array();
        foreach ((array) $conditions as $k => $v) {
            if (is_numeric($k)) {
                $where[] = ' '.$v;
            } else {
                $where[] = ' '.$k.'='.$this->getDB()->quote($v);
            }
        }
        

        if ($needPreQuery && ($limit || $offset)) {
            $preQuery = 'SELECT a.'.$entityClasses['a']::getIdentifier().' FROM'.$entityClasses['a']::getTableName().' a ';
            
            if ($where) {
                $preQuery .= ' WHERE'.implode(' AND ', $where);
            }
            if ($limit || $offset) {
                $preQuery .= ' LIMIT '.(int)$limit.((int)$offset ? ' OFFSET '.(int)$offset : '');
            }
            
            $preStmt = $this->getDB()->prepare($preQuery);
            $preStmt->execute(array_values((array) $values));
            $inIds = $preStmt->fetchAll(\PDO::FETCH_COLUMN);
            $placeholders = array_merge($placeholders, (array) $inIds);
            array_unshift($where, 'a.'.$entityClasses['a']::getIdentifier().' IN ('.implode(',', array_fill(0, count($inIds), '?')).') ');
        }
        
        if ($where) {
            $query .= ' WHERE'.implode(' AND ', $where);
        }
        $placeholders = array_merge($placeholders, (array) $values);  
        
        
        if ($order) {
            $query .= ' ORDER BY a.'.$order.' ';
        }
        
        if (!$needPreQuery && ($limit || $offset)) {
            $query .= ' LIMIT '.(int)$limit.((int)$offset ? ' OFFSET '.(int)$offset : '');
        }
        
        
        
        $stmt = $this->getDB()->prepare($query);
        $stmt->execute(array_values((array) $placeholders));
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $returnData = array();
        $relationTemp = array();
        
        foreach ($results as $row) {
            foreach ($entityClasses as $prefix => $entityClass) {
                if (!empty($row[$prefix.'_'.$entityIdentifiers[$prefix]]) && empty($returnData[$prefix][$row[$prefix.'_'.$entityIdentifiers[$prefix]]])) {
                    $returnData[$prefix][$row[$prefix.'_'.$entityIdentifiers[$prefix]]] = $entityRepositories[$prefix]->create($this->filter($row, $prefix), false);
                }
            }

            foreach ($relations as $rel) {
                if (!empty($relationTemp[$rel[0].'_'.$row[$rel[0].'_'.$entityIdentifiers[$rel[0]]].'|'.$rel[2].'_'.$row[$rel[2].'_'.$entityIdentifiers[$rel[2]]]])) {
                    continue;
                }
                if (isset($rel[4][3]) && $rel[4][3] !== true) {
                    if (!empty($row[$rel[0].'_'.$entityIdentifiers[$rel[0]]]) && !empty($row[$rel[2].'_'.$entityIdentifiers[$rel[2]]]) && !empty($row[$rel[4][3].'_'.$rel[4][1]]) && !empty($row[$rel[4][3].'_'.$rel[4][2]]) && ($row[$rel[0].'_'.$entityIdentifiers[$rel[0]]] == $row[$rel[4][3].'_'.$rel[4][1]]) && ($row[$rel[2].'_'.$entityIdentifiers[$rel[2]]] == $row[$rel[4][3].'_'.$rel[4][2]])) {
                        $relationTemp[$rel[0].'_'.$row[$rel[0].'_'.$entityIdentifiers[$rel[0]]].'|'.$rel[2].'_'.$row[$rel[2].'_'.$entityIdentifiers[$rel[2]]]] = true;
                        $returnData[$rel[0]][$row[$rel[0].'_'.$entityIdentifiers[$rel[0]]]]->add($rel[1], $returnData[$rel[1]][$row[$rel[2].'_'.$entityIdentifiers[$rel[2]]]]);
                    }
                } else {   
                    if (!empty($row[$rel[0].'_'.$rel[4][1]]) && !empty($row[$rel[2].'_'.$rel[4][2]]) && ($row[$rel[0].'_'.$rel[4][1]] == $row[$rel[2].'_'.$rel[4][2]])) {
                        $relationTemp[$rel[0].'_'.$row[$rel[0].'_'.$entityIdentifiers[$rel[0]]].'|'.$rel[2].'_'.$row[$rel[2].'_'.$entityIdentifiers[$rel[2]]]] = true;
                        if (!empty($rel[3][3])) {
                            $returnData[$rel[0]][$row[$rel[0].'_'.$entityIdentifiers[$rel[0]]]]->set($rel[1], $returnData[$rel[1]][$row[$rel[2].'_'.$entityIdentifiers[$rel[2]]]]);
                        } else {
                            $returnData[$rel[0]][$row[$rel[0].'_'.$entityIdentifiers[$rel[0]]]]->add($rel[1], $returnData[$rel[1]][$row[$rel[2].'_'.$entityIdentifiers[$rel[2]]]]);
                        }
                    }
                }
            
            }
            
        }
        
        return $returnData['a'];
        
    }
    
    public static function filter($data, $prefix)
    {
        $return = array();
        foreach ($data as $key => $entry) {
            if (strpos($key, $prefix.'_') === 0) {
                $return[substr($key, strlen($prefix+1))] = $entry;
            }
        }
        return $return;
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
