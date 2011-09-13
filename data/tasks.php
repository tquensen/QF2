<?php
$config['tasks'] = array();

$config['tasks']['example'] = array(
    'module' => 'default',
    'task' => 'exampleTask',
    'parameter' => array(
        'foo' => 'defaultFoo',
        'bar' => false,
        'baz' => 'BAZ'
    ),
    'assign' => array('bar', 'baz')
);