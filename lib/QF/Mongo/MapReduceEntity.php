<?php
namespace QF\Mongo;

abstract class MapReduceEntity extends Entity
{
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
                          //assumes n:1 or n:m relation if collection is set, 1:1 or 1:n otherwise
        ),
         */
        
        '_id' => array(
            'column' => true,
            //'exclude' => true
        ),
        'value' => array(
            'column' => true,
            //'exclude' => true
        ),
        
        /*
         * example structure for { _id: { tag: "key", count: 123 }, value: { foo: "xyz", bar: "abc" } } 
         * you can add 'exclude' => true to _id and value if you define every sub-property
         */
        'tag' => array(
            'container' => '_id',
            'type' => 'string',
            'required' => true
         ),
        'count' => array(
            'container' => '_id',
            'type' => 'int',
            'required' => true
         ),
        'foo' => array(
            'container' => 'value'
         ),
        'bar' => array(
            'container' => 'value'
         )
    );
    
    /**
     * @var mixed;
     */
    protected $_id;
    
    /**
     * @var mixed;
     */
    protected $value;
}