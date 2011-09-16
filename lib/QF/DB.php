<?php
namespace QF;

class DB
{
    /**
     * @var \QF\Core
     */
    protected $qf = null;
    protected $connections = array();

    /**
     * initializes a PDO object as configured in $qf_config['db']
     *
     * $qf_config['db'] must be an array of arrays with the following elements:
     * 'driver' => 'mysql:host=localhost;dbname=qfdb', //a valid PDO dsn. @see http://de3.php.net/manual/de/pdo.construct.php
     * 'username' => 'root', //The user name for the DSN string. This parameter is optional for some PDO drivers.
     * 'password' => '', //The password for the DSN string. This parameter is optional for some PDO drivers.
     * 'options' => array() //A key=>value array of driver-specific connection options. (optional)
     *
     */
    public function __construct(qfCore $qf)
    {
        $this->qf = $qf;
    }

    /**
     * initializes and returns a db connection as configured in $qf_config['db'][$connection]
     * @return PDO the database instance
     */
    public function get($connection = 'default')
    {
        if (!isset($this->connections[$connection])) {
            $db = $this->qf->getConfig('db');
            if (is_array($db) && isset($db[$connection])) {
                $this->connections[$connection] = new PDO(
                    $db['driver'],
                    isset($db['username']) ? $db['username'] : '',
                    isset($db['password']) ? $db['password'] : '',
                    isset($db['options']) ? $db['options'] : array()
                );

                if ($this->connections[$connection] && $this->connections[$connection]->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
                    $this->connection->exec('SET CHARACTER SET utf8');
                }
            }
        }
        return $this->connections[$connection];
    }
    
    public function buildEntities(\PDOStatement $statement, $entities, $return = null)
    {
        if (is_string ($entities)) {
            $entities = array(0 => array($entities));
        }
        foreach ($entities as $k => $v) {
            if (!is_array($v)) {
                $entities[$k] = array($v, 'id', array());
            } else {
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
                        $relData = array($relData, false);
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
                        if (!empty($relation[3])) {
                            $returnData[$relation[0]][$row[$identifiers[$relation[0]]]]->{$relation[2]} = $returnData[$relation[1]][$row[$identifiers[$relation[1]]]];
                        } else {
                            $returnData[$relation[0]][$row[$identifiers[$relation[0]]]]->{$relation[2]}[$row[$identifiers[$relation[1]]]] =  $returnData[$relation[1]][$row[$identifiers[$relation[1]]]];
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
