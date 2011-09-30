<?php

/**
 * @property int $id
 * @property mixed $property
 * @property string $property2
 * @property array $children
 */
class TestEntity extends Entity
{
    protected static $_properties = array(
        'id' => array('type' => 'int', 'container' => 'data'),
        'property' => array('container' => 'data'),
        'property2' => array(
            'container' => 'data',
            'type' => 'string', //a scalar type or a classname, true to allow any type, default = true
            'default' => 'Katze' // the default value to return by get if null, and to set by clear, default = null
        ),
        'children' => array(
            'type' => 'TestEntity', //a scalar type or a classname, true to allow any type, default = true
            'readonly' => false, //only allow read access (get, has, is)
            'collection' => true, //stores multiple values, activates add and remove methods, true to store values in an array, name of a class that implements ArrayAccess to store values in that class, default = false (single value),
            'collectionUnique' => 'id', //do not allow dublicate entries when using as collection, when type = array or an object and collectionUnique is a string, that property/key will be used as index of the collection
            'collectionSingleName' => 'child',
            'exclude' => false, //set to true to exclude this property on toArray(), default = false
            'default' => array() // the default value to return by get if null, and to set by clear, default = null
        )
    );
    
    protected $data = array();
    protected $children;
}
