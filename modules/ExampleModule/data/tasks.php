<?php
//tasks are loaded from the container by 'service', then method 'task' is called with the parameters as first parameter
//the task object/service is loaded from the DI container (see dependencies.php for example)

/*
$tasks['example'] = array(
    'service' => 'examplemodule.task.example',
    'task' => 'exampleTask',
    'parameter' => array(
        'foo' => 'defaultFoo',
        'bar' => false,
        'baz' => 'BAZ'
    ),
    'assign' => array('bar', 'baz')
);
 */