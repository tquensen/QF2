<?php
$routes = array();

$routes['error401_route'] = array(
    'controller' => '\\DefaultModule\\Controller\\Error',
    'action' => 'error401',
    'parameter' => array('message' => null, 'exception' => null)
);
$routes['error403_route'] = array(
    'controller' => '\\DefaultModule\\Controller\\Error',
    'action' => 'error403',
    'parameter' => array('message' => null, 'exception' => null)
);
$routes['error404_route'] = array(
    'controller' => '\\DefaultModule\\Controller\\Error',
    'action' => 'error404',
    'parameter' => array('message' => null, 'exception' => null)
);
$routes['error500_route'] = array(
    'controller' => '\\DefaultModule\\Controller\\Error',
    'action' => 'error500',
    'parameter' => array('message' => null, 'exception' => null)
);

return $routes;