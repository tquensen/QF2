<?php
namespace Mongo;

class MapReduce
{
    protected $collection = null;
    
    protected $map = null;
    protected $reduce = null;
    protected $finalize = null;
    
    protected $outCollection = null;
    protected $outType = null;
    protected $outEntity = null;
    
    protected $query = array();
    protected $sort = array();
    protected $limit = null;
    
    protected $scope = array();
    
    protected $result = null;
    
    /**
     *
     * @var MongoDB
     */
    protected $db = null;
    protected $connection = null;
    
    /**
     *
     * @param string $collection the collection to use for the map/reduce
     * @param string $connection the connection name
     * @param MongoCode|string $map the map function as MongoCode or string
     * @param MongoCode|string $reduce the reduce function as MongoCode or string
     * @param MongoCode|string $finalize the finalize function as MongoCode or string
     */
    public function __construct($collection = null, $connection = null, $map = null, $reduce = null, $finalize = null)
    {
        $this->collection = $collection;
        $this->connection = $connection;
        $this->db = DB::get($this->connection);
    
        if ($map) {
            $this->map($map);
        }
        if ($reduce) {
            $this->reduce($reduce);
        }
        if ($finalize) {
            $this->finalize($finalize);
        }
    }
    
    /**
     * the map function
     * 
     * example:
     * function() { emit(this.username, {count: 1, likes: this.likes} ); }
     * 
     * @param \MongoCode|string $map the map function as MongoCode or string
     * @return MapReduce 
     */
    public function map($map)
    {
        if (is_string($map)) {
            $map = new \MongoCode($map);
        }
        if (!is_object($map) || !($map instanceof \MongoCode)) {
            throw new Exception('string, or \\MongoCode expected for $map, ' . get_class($map) . ' given!');
        }
        $this->map = $map;
        return $this;
    }
    
    /**
     * the reduce function
     * 
     * example:
     * function(key, values) {
     *   var result = {count: 0, likes: 0};
     *   values.forEach(function(value) {
     *     result.count += value.count;
     *     result.likes += values.likes;
     *   });
     *   return result;
     * }
     * 
     * @param \MongoCode|string $reduce the reduce function as MongoCode or string
     * @return MapReduce 
     */
    public function reduce($reduce)
    {
        if (is_string($reduce)) {
            $reduce = new \MongoCode($reduce);
        }
        if (!is_object($reduce) || !($reduce instanceof \MongoCode)) {
            throw new Exception('string, or \\MongoCode expected for $reduce, ' . get_class($reduce) . ' given!');
        }
        $this->reduce = $reduce;
        return $this;
    }
    
    /**
     * the (optional) finalize function
     * 
     * example:
     * function finalize(key, value) { return value; }
     *
     * @param \MongoCode|string $finalize the finalize function as MongoCode or string
     * @return MapReduce 
     */
    public function finalize($finalize)
    {
        if (is_string($finalize)) {
            $finalize = new \MongoCode($finalize);
        }
        if (!is_object($finalize) || !($finalize instanceof \MongoCode)) {
            throw new Exception('string, or \\MongoCode expected for $finalize, ' . get_class($finalize) . ' given!');
        }
        $this->finalize = $finalize;
        return $this;
    }
    /**
     * @param array $query The fields for which to search.
     * @param array $sort The fields by which to sort.
     * @param int $limit The number of results to return.
     * @return MapReduce 
     */
    public function filter($query = array(), $sort = array(), $limit = null)
    {
        $this->query = $query;
        $this->sort = $sort;
        $this->limit = $limit;
        
        return $this;
    }
    
    /**
     *
     * @param string|array $out the output collection as string or an array('outType' => 'outCollection')
     * @param string $outType the out type ('replace', 'merge', 'reduce', 'inline'), default is 'replace'
     * @return MapReduce 
     */
    public function out($out, $outType = 'replace')
    {
        if (is_array($out))
        {
            $outTmp = reset($out);
            $outTypeTmp = key($out);
            
            $out = $outTmp;
            $outType = is_string($outTypeTmp) ? $outTypeTmp : $outType;
        }
        
        $this->outCollection = $out;
        $this->outType = $outType;
        
        return $this;
    }
    
    /**
     *
     * @param string $entityClass the name of the entity class to use for the results
     * @param string $outType the out type ('replace', 'merge', 'reduce'), default is 'replace', inline is not supported!
     * @return MapReduce 
     */
    public function outEntity($entityClass, $outType = 'replace')
    {
        $this->outType = $outType;
        
        if (!is_subclass_of($entityClass, '\\Mongo\\Entity')) {
            throw new \InvalidArgumentException('$entityClass must be an \\Mongo\\Entity classname');
        }
        
        $this->outCollection = $entityClass::getCollectionName();
        
        $this->outEntity = $entityClass;
        
        return $this;
    }
    
    /**
     *
     * @param type $returnResult true (default) to return the result of the map/reduce call, false to return $this (for chaining)
     * @return Mongo_MapReduce|array 
     */
    public function execute($returnResult = true)
    {
        $this->check();
        
        $options = array(
            'mapreduce' => $this->collection,
            'map' => $this->map,
            'reduce' => $this->reduce,
            'out' => array($this->outType => $this->outCollection)
        );
        
        if ($this->finalize) {
            $options['finalize'] = $this->finalize;
        }
        
        if ($this->query) {
            $options['query'] = $this->query;
        }
        if ($this->sort) {
            $options['sort'] = $this->sort;
        }
        if ($this->limit) {
            $options['limit'] = $this->limit;
        }
        if ($this->scope) {
            $options['scope'] = $this->scope;
        }

        $this->result = $this->db->command($options);
        
        return $returnResult ? $this->result : $this;
    }
    
    public function getResult()
    {
        return $this->result;
    }
    
    /**
     *
     * @param array $query The fields for which to search.
     * @param array $sort The fields by which to sort.
     * @param int $limit The number of results to return.
     * @param int $skip The number of results to skip.
     * @param bool $build (default true) set to false to return the raw MongoCursor
     * @return MongoCursor|array Returns an array or a cursor for the search results.
     */
    public function find($query = array(), $sort = array(), $limit = null, $skip = null, $build = true)
    {
        if (!$this->result) {
            $result = $this->execute();
        }
        
        if ($this->outEntity) {
            $entityName = $this->outEntity;
            $repositoryName = $entityName::getRepositoryClass();
            $repository = new $repositoryName($this->connection, $entityName);
            return $repository->find($query, $sort, $limit, $skip, $build);
        }
        
        if (!empty($result['results'])) {
            return $result['results'];
        }
        
        if (!empty($result['result'])) {
            $cursor = $query ? $this->db->selectCollection($result['result'])->find($query) : $this->db->selectCollection($result['result'])->find();
            if ($sort) {
                $cursor->sort($sort);
            }
            if ($limit) {
                $cursor->limit($limit);
            }
            if ($skip) {
                $cursor->skip($skip);
            }
            return $cursor;
        }
        return false;
    }
    
    protected function check()
    {
        if (!$this->collection) {
            throw new \Exception('Missing collection to use for map/reduce.');
        }
        
        if (!$this->map) {
            throw new \Exception('Missing map function.');
        }
        
        if (!$this->reduce) {
            throw new \Exception('Missing reduce function.');
        }
        
        if (!$this->outCollection) {
            throw new \Exception('Missing out collection.');
        }
    }
}
