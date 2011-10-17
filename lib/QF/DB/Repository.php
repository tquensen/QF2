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
    
    public function __construct($db = null, $entityClass = null)
    {
        $this->db = $db;

        if ($entityClass) { 
            if (is_object($entityClass)) {
                $entityClass = get_class($entityClass);
            }
            
            $this->entityClass = $entityClass;
        }
        if (!is_subclass_of($this->entityClass, '\\QF\\DB\\Entity')) {
            throw new \InvalidArgumentException('$entityClass must be an \\QF\\DB\\Entity instance or classname');
        }
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
            $entityClass = $this->entityClass;
        }
        $entity = new $entityClass($this->getDB());
        $isNew ? $entity->postCreate() : $entity->postLoad();
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
            if (is_object($entry))
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
                $result = $query->execute($entity->{$entityClass::getIdentifier()});
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
                    $query->execute(is_object($entity) ? $entity->{$entityClass::getIdentifier()} : $entity);
                }
            }

        } catch (Exception $e) {
            throw $e;
        }
		return $result;
	}
    
    public function removeBy($condition, $values, $cleanRefTable = false)
	{
        $entityClass = $this->entityClass;
        $query = 'DELETE FROM '.$entityClass::getTableName();
                
        $where = '';
        foreach ((array) $conditions as $k => $v) {
            if (is_numeric($k)) {
                $where .= ' '.$v;
            } else {
                $where .= ' '.$k.'='.$this->getDB()->quote($v);
            }
        }
        if ($where) {
            $query .= ' WHERE'.$where;
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
        $entityClass = $this->entityClass;
        foreach ($entityClass::getRelations() as $relation => $info) {
            if (!isset($info[3]) || $info[3] === true) {
                continue;
            }
            $stmt = $this->getDB()->prepare('SELECT a_b.id FROM '.$info[3].' a_b LEFT JOIN '.$entityClass::getTableName().' a ON a_b.'.$info[1].' = a.'.$entityClass::getIdentifier().' WHERE a.'.$entityClass::getIdentifier().' IS NULL')->execute();
            $refTableIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $deleteStmt = $this->getDB()->prepare('DELETE FROM '.$info[3].' WHERE id IN ('.implode(',', array_fill(0, count($refTableIds), '?')).')')->execute($refTableIds);
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
        $entity = $this->entityClass;

        if (!is_subclass_of($entity, '\\QF\\DB\\Entity')) {
            throw new \InvalidArgumentException('$entity must be an \\QF\\DB\\Entity instance or classname');
        }
        $query = 'SELECT '.implode(', ', $entity::getColumns()).' FROM '.$entity::getTableName();
        $where = '';
        foreach ((array) $conditions as $k => $v) {
            if (is_numeric($k)) {
                $where .= ' '.$v;
            } else {
                $where .= ' '.$k.'='.$this->getDB()->quote($v);
            }
        }
        if ($where) {
            $query .= ' WHERE'.$where;
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
     * builds entities from a PDOStatement as defined by $entities
     * 
     * example for $entities:
     *  - if null, the current entity class of this repository is implied
     *  - $entities must contain either a single entity or an array of multiple entity definitions with prefix as keys
     *  - an single entity is defined either by
     *    - the name of an entity class as string (e.g. "\Foo\Model\User")
     *    - or by an array(classname, relations-array (optional))
     *  - the relations-array consists of one or more "relationname" => $relation pairs, where
     *    - relationname is the name of the property where the related entry is stored, and
     *    - $relation is the prefix of the related entity as string "prefix"
     * 
     * examples:
     *   - "\Foo\Model\User"
     *   - array('\Foo\Model\User')
     *   - array('a' => array('\Foo\Model\User', array('addresses' => 'b')), 'b' => '\Foo\Model\Addresses');
     *   - array('a' => array('\Foo\Model\User', array('profile' => 'b')), 'b' => array('\Foo\Model\Profile'));
     * 
     * @param \PDOStatement $statement the pdo statement
     * @param mixed $entities a definition of the entities to return
     * @param mixed $return if multiple entities are definded: true to return an array of all fetched entities or the prefix of an entity, null for the first defined entity
     * @return array the resulting entities 
     */
    public function build(\PDOStatement $statement, $entities = null, $return = null)
    {
        if ($entities === null) {
            $entities = $this->entityClass;
        }
        if (is_string ($entities)) {
            $entities = array(0 => array($entities));
        } elseif(!empty($entities[0])) {
            $entities = array(0 => $entities);
        }
        foreach ($entities as $k => $v) {
            if (!is_array($v)) {
                $entities[$k] = array($v, array());
            } else {
                if (substr($v[0], 0, 1) !== '\\') {
                    $v[0] = '\\'.$v[0];
                }
                $entities[$k] = array($v[0], isset($v[1]) ? $v[1] : array());
            }
        }
        
        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $returnData = array();
        if (count($entities) === 1 && !empty($entities[0])) {
            $key = $entities[0][0]::getIdentifier();
            foreach ($results as $row) {
                $entity = $this->create($row, false, $entities[0][0]);
                $returnData[$entity->$key] = $entity;
                $return = true;
            }
        } else {
            if ($return === null) {
                $entityKeys = array_keys($entities);
                $return = reset($entityKeys);
            }

            $relations = array();
            $identifiers = array();
            foreach ($entities as $k => $entity) {
                $identifiers[$k] = $k.'_'.$entity[0]::getIdentifier();
                
                if (empty($entity[1])) {
                    continue;
                }
                foreach ($entity[1] as $relKey => $relData) {
                    if (isset($entities[$relData])) {
                        $relInfo = $entity[0]::getRelation($relData);                            
                        $relations[] = array($k, $relData, $relKey, empty($relInfo[3]) || $relInfo[3] !== true);
                    }
                } 
            }
            foreach ($results as $row) {
                foreach ($entities as $prefix => $entity) {
                    if ($row[$identifiers[$prefix]] && !isset($returnData[$prefix][$row[$identifiers[$prefix]]])) {
                        $entityName = $entity[0];
                        $fields = $this->_filter($row, $alias);
                        $entity = $this->create($fields, false, $entityName);
                        foreach ($fields as $k => $v) {
                            $entity->setDatabaseProperty($k, $v);
                        }
                        $returnData[$prefix][$row[$identifiers[$prefix]]] = $entity;
                    }
                }
                foreach ($relations as $relation) {
                    if ($row[$identifiers[$relation[0]]] && $row[$identifiers[$relation[1]]]) {
                        if (empty($relation[3])) {
                            $returnData[$relation[0]][$row[$identifiers[$relation[0]]]]->{'add'.ucfirst($relation[2])}($data);
                        } else {
                            $returnData[$relation[0]][$row[$identifiers[$relation[0]]]]->{$relation[2]} = $returnData[$relation[1]][$row[$identifiers[$relation[1]]]];
                        }
                    }
                }
            }
        }
        
        return $return === true ? $returnData : (isset($returnData[$return]) ? $returnData[$return] : array()); 
    }
    
    protected function _filter($row, $prefix = '')
    {
        if (!$prefix) {
            return $row;
        }
        $prefix = $prefix.'_';
        $length = strlen($prefix);
        $return = array();
        foreach ($row as $k=>$v) {
            if (substr($k, 0, $length) == $prefix) {
               $return[substr($k, $length)] = $v;
            }
        }
        return $return;
    }
}
