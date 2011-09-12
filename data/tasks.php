<?php
$qf_config['tasks'] = array();

$qf_config['tasks']['example'] = array(
    'module' => 'default',
    'task' => 'exampleTask',
    'parameter' => array(
        'foo' => 'defaultFoo',
        'bar' => false,
        'baz' => 'BAZ'
    ),
    'assign' => array('bar', 'baz')
);