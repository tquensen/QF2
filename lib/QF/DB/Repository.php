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

    protected $connection = null;
    
    public function __construct($connection = null, $entityClass = null)
    {
        $this->connection = $connection;

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
     * @return MongoDB
     */
    public function getDB()
    {
        if ($this->db === null) {
            $this->db = DB::get($this->connection);
        }
        return $this->db;
    }
    
    public function save(Entity $entity)
    {
        $entityClass = get_class($entity);
        $properties = $entityClass::getColumns();
        $identifier = $entityClass::getIdentifier();
        
        if ($entity->isNew()) {
            if ($entity->$identifier === null) {
                unset($properties[$identifier]);
            }
            $query = 'INSERT INTO '.$entityClass::getTablename().' ('.implode(',',$properties).') VALUES ('.implode(',', array_fill(0, count($properties), '?')).')';
        } else {
            $idType = $properties[$identifier];
            unset($properties[$identifier]);
            $query = 'UPDATE '.$entityClass::getTablename().' SET '.implode('=?, ',$properties).'=? WHERE '.$identifier.' = ?';
            $properties[$identifier] = $idType;
        }
        
        $stmt = $this->getDB()->prepare($query);
        
        $values = array();
        foreach ($properties as $prop => $type) {
            $values[] = $entity->$property;
        }
        
        $result = $stmt->execute();
        if ($result && $insert && $entity->$identifier === null) {
            $entity->$identifier = $this->getDB()->lastInsertId();
        } 
        return $result;
    }
    
    /**
     *
     * @param mixed $entity an \QF\DB\Entity instance or classname
     * @param array|string $conditions the where conditions
     * @param array $values values for ?-placeholders in the conditions
     * @param string $order an order by clause (id ASC, foo DESC)
     * @return Entity
     */
    public function load($entity, $conditions = array(), $values = array(), $order = null)
    {
        $entities = $this->loadEntities($entity, $conditions, $values, $order, 1, 0, true);
        return reset($entities);
    }
    
    /**
     *
     * @param mixed $entity an \QF\DB\Entity instance or classname
     * @param array|string $conditions the where conditions
     * @param array $values values for ?-placeholders in the conditions
     * @param string $order an order by clause (id ASC, foo DESC)
     * @param int $limit
     * @param int $offset
     * @param bool $build true to build the entities, false to return the statement
     * @return array|\PDOStatement
     */
    public function loadEntities($entity, $conditions = array(), $values = array(), $order = null, $limit = null, $offset = null, $build = true)
    {
        if (is_object($entity)) {
            $entity = get_class($entity);
        }
        if (!is_subclass_of($entity, '\\QF\\DB\\Entity')) {
            throw new \InvalidArgumentException('$entity must be an \\QF\\DB\\Entity instance or classname');
        }
        $query = 'SELECT '.implode(', ', $entity::getColumns()).' FROM '.$entity::getTablename();
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
            return $this->buildEntities($stmt, array($entity, $entity::getIdentifier()));
        } else {
            return $stmt;
        }     
    }
    
    /**
     * builds entities from a PDOStatement as defined by $entities and returns the first entity
     * 
     * example for $entities:
     *  - $entities must contain either a single entity or an array of multiple entity definitions with prefix as keys
     *  - an single entity is defined either by
     *    - the name of an entity class as string (e.g. "\Foo\Model\User" or just "stdClass") - ("id" as index and no relations implied)
     *    - or by an array(classname (e.g. "\Foo\Model\User" or just "stdClass"), index-field (default is "id"), relations-array (optional))
     *  - the relations-array consists of one or more "relationname" => $relation pairs, where
     *    - relationname is the name of the property where the related entry is stored, and
     *    - $relation is either the prefix of the related entity as string (1:n) or an array("prefix", true) (1:1)
     * 
     * examples:
     *   - "stdClass" (this is the default)
     *   - "\Foo\Model\User"
     *   - array('\Foo\Model\User', 'id')
     *   - array('a' => array('\Foo\Model\User', 'id', array('addresses' => 'b')), 'b' => '\Foo\Model\Addresses');
     *   - array('a' => array('\Foo\Model\User', 'id', array('profile' => array('b', true))), 'b' => array('\Foo\Model\Profile', 'id'));
     * 
     * @param \PDOStatement $statement the pdo statement
     * @param mixed $entities a definition of the entities to return (default is "stdClass")
     * @return Object the resulting entity 
     */
    public function buildEntity(\PDOStatement $statement, $entities = "stdClass") {
        $return = $this->buildEntities($statement, $entities, null);
        return reset($return);
    }
    
    /**
     * builds entities from a PDOStatement as defined by $entities
     * 
     * example for $entities:
     *  - $entities must contain either a single entity or an array of multiple entity definitions with prefix as keys
     *  - an single entity is defined either by
     *    - the name of an entity class as string (e.g. "\Foo\Model\User" or just "stdClass") - ("id" as index and no relations implied)
     *    - or by an array(classname, index-field (default is "id"), relations-array (optional))
     *  - the relations-array consists of one or more "relationname" => $relation pairs, where
     *    - relationname is the name of the property where the related entry is stored, and
     *    - $relation is either the prefix of the related entity as string "prefix" (1:1) or as array("prefix") (1:n)
     * 
     * examples:
     *   - "stdClass" (this is the default)
     *   - "\Foo\Model\User"
     *   - array('\Foo\Model\User', 'id')
     *   - array('a' => array('\Foo\Model\User', 'id', array('addresses' => array('b'))), 'b' => '\Foo\Model\Addresses');
     *   - array('a' => array('\Foo\Model\User', 'id', array('profile' => 'b')), 'b' => array('\Foo\Model\Profile', 'id'));
     * 
     * @param \PDOStatement $statement the pdo statement
     * @param mixed $entities a definition of the entities to return  (default is "stdClass")
     * @param mixed $return if multiple entities are definded: true to return an array of all fetched entities or the prefix of an entity, null for the first defined entity
     * @return array the resulting entities 
     */
    public function buildEntities(\PDOStatement $statement, $entities = "stdClass", $return = null)
    {
        if (is_string ($entities)) {
            $entities = array(0 => array($entities));
        } elseif(!empty($entities[0])) {
            $entities = array(0 => $entities);
        }
        foreach ($entities as $k => $v) {
            if (!is_array($v)) {
                $entities[$k] = array($v, 'id', array());
            } else {
                if (substr($v[0], 0, 1) !== '\\') {
                    $v[0] = '\\'.$v[0];
                }
                $entities[$k] = array($v[0], isset($v[1]) ? $v[1] : 'id', isset($v[2]) ? $v[2] : array());
            }
        }
        
        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $returnData = array();
        if (count($entities) === 1 && !empty($entities[0])) {
            $key = isset($entities[0][1]) ? $entities[0][1] : 'id';
            foreach ($results as $row) {
                $entity = $entities[0][0];
                $entity = new $entityName();
                foreach ($row as $k => $v) {
                    $entity->$k = $v;
                }
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
                $identifiers[$k] = $k.'_'.$entity[1];
                
                if (empty($entity[2])) {
                    continue;
                }
                foreach ($entity[2] as $relKey => $relData) {
                    if (is_string($relData)) {
                        $relData = array($relData, true);
                    }
                    if (isset($entities[$relData[0]])) {
                        $relations[] = array($k, $relData[0], $relKey, !empty($relData[1]));
                    }
                } 
            }
            foreach ($results as $row) {
                foreach ($entities as $prefix => $entity) {
                    if ($row[$identifiers[$prefix]] && !isset($returnData[$prefix][$row[$identifiers[$prefix]]])) {
                        $entityName = $entity[0];
                        $entity = new $entityName();
                        foreach ($this->_filter($row, $alias) as $k => $v) {
                            $entity->$k = $v;
                        }
                        $returnData[$prefix][$row[$identifiers[$prefix]]] = $entity;
                    }
                }
                foreach ($relations as $relation) {
                    if ($row[$identifiers[$relation[0]]] && $row[$identifiers[$relation[1]]]) {
                        if (empty($relation[3])) {
                            $data = $returnData[$relation[0]][$row[$identifiers[$relation[0]]]]->{$relation[2]};
                            $data[$row[$identifiers[$relation[1]]]] =  $returnData[$relation[1]][$row[$identifiers[$relation[1]]]];
                            $returnData[$relation[0]][$row[$identifiers[$relation[0]]]]->{$relation[2]} = $data;
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
