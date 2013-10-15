<?php
//example controller routes

$routes['example.index'] = array(
    'url' => 'example(.:_format:)', 
    //'url' => array('de' => 'beispiel(.:_format:)', 'default' => 'example(.:_format:)'), //i18n urls: array('language' => 'url', 'default' => 'fallback url')
    'service' => 'examplemodule.controller.example',
    'action' => 'indexAction',
    'method' => 'GET',
    'parameter' => array('_format' => false),
    'patterns' => array('_format' => '(json|html)')
);

$routes['example.create'] = array(
    'url' => 'example/create(.:_format:)',
    'service' => 'examplemodule.controller.example',
    'action' => 'createAction',
    'method' => array('GET', 'POST'),
    'parameter' => array('_format' => false),
    'patterns' => array('_format' => '(json|html)')
);

$routes['example.update'] = array(
    'url' => 'example/:id:/update(.:_format:)',
    'service' => 'examplemodule.controller.example',
    'action' => 'updateAction',
    'method' => array('GET', 'POST'),
    'parameter' => array('id' => false, '_format' => false),
    'patterns' => array('_format' => '(json|html)')
);

$routes['example.delete'] = array(
    'url' => 'example/:id:/delete(.:_format:)',
    'service' => 'examplemodule.controller.example',
    'action' => 'deleteAction',
    'method' => 'DELETE',
    'parameter' => array('id' => false, '_format' => false),
    'patterns' => array('_format' => '(json|html)')
);

$routes['example.show'] = array(
    'url' => 'example/:id:(.:_format:)',
    'service' => 'examplemodule.controller.example',
    'action' => 'showAction',
    'method' => 'GET',
    'parameter' => array('id' => false, '_format' => false),
    'patterns' => array('_format' => '(json|html)')
);



//default static pages / fallback (this must be the LAST route!)
$routes['static'] = array(
    'url' => ':page:(.:_format:)',
    'service' => 'examplemodule.controller.base',
    'action' => 'staticPageAction',
    'method' => 'GET',
    'parameter' => array('page' => false, '_format' => false),
    'patterns' => array('_format' => '(json|html)')
);