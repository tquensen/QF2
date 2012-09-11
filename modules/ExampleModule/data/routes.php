<?php
//example controller routes

$routes['example.index'] = array(
    'url' => 'example(.:_format:)', 
    //'url' => array('de' => 'beispiel(.:_format:)', 'default' => 'example(.:_format:)'), //i18n urls: array('language' => 'url', 'default' => 'fallback url')
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'index',
    'method' => 'GET',
    'parameter' => array('_format' => false),
    'patterns' => array('_format' => '(json|html)')
);

$routes['example.create'] = array(
    'url' => 'example/create(.:_format:)',
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'create',
    'method' => array('GET', 'POST'),
    'parameter' => array('_format' => false),
    'patterns' => array('_format' => '(json|html)')
);

$routes['example.update'] = array(
    'url' => 'example/:id:/update(.:_format:)',
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'update',
    'method' => array('GET', 'POST'),
    'parameter' => array('id' => false, '_format' => false),
    'patterns' => array('_format' => '(json|html)')
);

$routes['example.delete'] = array(
    'url' => 'example/:id:/delete(.:_format:)',
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'delete',
    'method' => 'DELETE',
    'parameter' => array('id' => false, '_format' => false),
    'patterns' => array('_format' => '(json|html)')
);

$routes['example.show'] = array(
    'url' => 'example/:id:(.:_format:)',
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'show',
    'method' => 'GET',
    'parameter' => array('id' => false, '_format' => false),
    'patterns' => array('_format' => '(json|html)')
);



//default static pages / fallback (this must be the LAST route!)
$routes['static'] = array(
    'url' => ':page:(.:_format:)',
    'controller' => '\\ExampleModule\\Controller\\Base',
    'action' => 'staticPage',
    'method' => 'GET',
    'parameter' => array('page' => false, '_format' => false),
    'patterns' => array('_format' => '(json|html)')
);