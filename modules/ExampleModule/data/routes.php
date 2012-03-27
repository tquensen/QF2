<?php
//default static pages / fallback (this must be the LAST route!)
$config['routes']['static'] = array(
    'url' => ':page:(.:_format:)',
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'staticPage',
    'method' => 'GET',
    'parameter' => array('page' => false, '_format' => false),
    'patterns' => array('_format' => '(json|html)')
);

$config['routes']['example.index'] = array(
    'url' => 'example(.:_format:)',
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'index',
    'method' => 'GET',
    'parameter' => array('_format' => false),
    'patterns' => array('_format' => '(json|html)')
);

$config['routes']['example.create'] = array(
    'url' => 'example/create(.:_format:)',
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'create',
    'method' => array('GET', 'POST'),
    'parameter' => array('_format' => false),
    'patterns' => array('_format' => '(json|html)')
);

$config['routes']['example.update'] = array(
    'url' => 'example/update(.:_format:)',
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'update',
    'method' => array('GET', 'POST'),
    'parameter' => array('_format' => false),
    'patterns' => array('_format' => '(json|html)')
);

$config['routes']['example.delete'] = array(
    'url' => 'example/delete(.:_format:)',
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'delete',
    'method' => 'POST',
    'parameter' => array('_format' => false),
    'patterns' => array('_format' => '(json|html)')
);

$config['routes']['example.show'] = array(
    'url' => 'example/:id:(.:_format:)',
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'show',
    'method' => 'GET',
    'parameter' => array('id' => false, '_format' => false),
    'patterns' => array('_format' => '(json|html)')
);