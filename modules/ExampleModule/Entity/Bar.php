<?php
namespace ExampleModule\Entity;

use QF\DB\Entity;

class Bar extends Entity
{
    protected static $tableName = 'example_bar';
    protected static $autoIncrement = true;
    protected static $identifier = 'id';
    protected static $repositoryClass = '\\QF\\DB\\Repository';
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
                          //assumes 1:n or n:m relation if collection is set, n:1 or 1:1 otherwise
            'refTable' => 'tablename' //for n:m relations - the name of the ref table, default = false
        )
         */
        
        'id'        => array('type' => 'int', 'column' => true),
        'title'     => array('type' => 'string', 'column' => true),
        'foos'      => array(
            'type' => '\\ExampleModule\Entity\\Foo',
            'collection' => true,
            'collectionUnique' => 'id', //index the collection by foos primary keys
            'collectionSingleName' => 'foo', //addFoo($oneFoo) instead of addFoos($oneFoo);
            'relation' => array('id', 'bar_id') 
        ),
    );
    
    /** @var int */
    protected $id;
    
    /** @var string */
    protected $title;
    
    /** @var array */
    protected $foos = array();


    public function preSave(\PDO $db)
    {

    }

    public function preRemove(\PDO $db)
    {
        
    }

    public function postCreate()
    {

    }

    public function postLoad()
    {

    }
    
    /**
     * Created the table for this model
     */
    public static function install($db, $installedVersion = 0, $targetVersion = 0)
    {
        switch ($installedVersion) {
            case 0:
                $sql = "CREATE TABLE ".static::getTableName()." (
                      ".static::getIdentifier()." INT(11) ".(static::isAutoIncrement() ? "AUTO_INCREMENT" : "").",
                          
                      title VARCHAR(255) NOT NULL,
                      
					  PRIMARY KEY (".static::getIdentifier().")

                      
					) ENGINE=INNODB DEFAULT CHARSET=utf8";

                $db->query($sql);
            case 1:
                if ($targetVersion && $targetVersion <= 1) break;
            /* //for every new version add your code below (including the lines "case NEW_VERSION:" and "if ($targetVersion && $targetVersion <= NEW_VERSION) break;")

                $sql = "ALTER TABLE ".static::getTableName()."
					  ADD something VARCHAR(255)";

                $db->query($sql);

            case 2:
                if ($targetVersion && $targetVersion <= 2) break;
             */
        }
        return true;
    }

    /**
     * Deletes the table for this model
     */
    public static function uninstall($db, $installedVersion = 0, $targetVersion = 0)
    {
        SWITCH ($installedVersion) {
            case 0:
            /* //for every new version add your code directly below "case 0:", beginning with "case NEW_VERSION:" and "if ($targetVersion >= NEW_VERSION) break;"
            case 2:
                if ($targetVersion >= 2) break;
                $sql = "ALTER TABLE ".static::getTableName()." DROP something";
                $db->query($sql);
             */
            case 1:
                if ($targetVersion >= 1) break;
                $sql = "DROP TABLE ".static::getTableName()."";
                $db->query($sql);
        }
        return true;
    } 
}