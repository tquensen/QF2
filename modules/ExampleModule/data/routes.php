<?php
$routes = array();

//default static pages / fallback (this must be the LAST route!)
$routes['static'] = array(
    'url' => ':page:(.:_format:)',
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'staticPage',
    'parameter' => array('page' => false, '_format' => false),
    'patterns' => array('_format' => '(json|html)')
);

return $routes;