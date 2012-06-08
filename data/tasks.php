<?php
$config['tasks']['example'] = array(
    'class' => '\\ExampleModule\\Task\\Example',
    'task' => 'exampleTask',
    'parameter' => array(
        'foo' => 'defaultFoo',
        'bar' => false,
        'baz' => 'BAZ'
    ),
    'assign' => array('bar', 'baz')
);

//load development Tasks on CLI access
if (QF_CLI === true) {
    $this->load(__DIR__.'/../modules/DevModule/data/tasks.php');
}
