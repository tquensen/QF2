<?php
namespace ExampleModule\Entity;

use QF\Mongo\Entity;

class MongoFoo extends Entity
{
    protected static $collectionName = 'foo';
    protected static $autoId = true;
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
                          //assumes 1:n or n:m relation if collection is set, 1:1 or n:1 otherwise
            'relationMultiple' => true //set to true for m:n relations (when either local_column or foreign_columns is an array) default = false
        ),
         */
        
        '_id'        => array('type' => '\\MongoId', 'column' => true),
        'title'         => array('type' => 'string', 'column' => true),
        'description'   => array('type' => 'string', 'column' => true),
        'bar_id'        => array('type' => '\\MongoId', 'column' => true),
        'bar'           => array(
            'type' => '\\ExampleModule\Entity\\MongoBar', 'relation' => array('bar_id', '_id')
        ),
    );
    
    /**
     * @var \MongoId
     */
    protected $_id;
    
    /** @var string */
    protected $title;
    
    /** @var string */
    protected $description;
    
    /** @var \MongoId */
    protected $bar_id;

    /** @var \ExampleModule\Entity\MongoBar */
    protected $bar;
    
    public function preSave(\MongoDB $db)
    {

    }

    public function preRemove(\MongoDB $db)
    {
        
    }

    public function postCreate()
    {

    }

    public function postLoad()
    {

    }
    
    
    /**
     * initiate the collection for this model
     */
    public static function install($db, $installedVersion = 0, $targetVersion = 0)
    {
        $collection = static::getRepository($db)->getCollection();
        switch ($installedVersion) {
            case 0:
                $collection->ensureIndex(array('bar_id' => 1), array('safe' => true, 'unique' => false));
            case 1:
                if ($targetVersion && $targetVersion <= 1) break;
            /* //for every new version add your code below (including the lines "case NEW_VERSION:" and "if ($targetVersion && $targetVersion <= NEW_VERSION) break;")

                $collection->ensureIndex(array('name' => 1), array('safe' => true));

            case 2:
                if ($targetVersion && $targetVersion <= 2) break;
             */
        }
        return true;
    }

    /**
     * remove the collection for this model
     */
    public static function uninstall($db, $installedVersion = 0, $targetVersion = 0)
    {
        $collection = static::getRepository($db)->getCollection();
        SWITCH ($installedVersion) {
            case 0:
            /* //for every new version add your code directly below "case 0:", beginning with "case NEW_VERSION:" and "if ($targetVersion >= NEW_VERSION) break;"
            case 2:
                if ($targetVersion >= 2) break;
                $collection->deleteIndex("name");
             */
            case 1:
                if ($targetVersion >= 1) break;
                $collection->drop();
        }
        return true;
    }
}