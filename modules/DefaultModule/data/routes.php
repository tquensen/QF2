<?php
$routes['error401'] = array(
    'controller' => '\\DefaultModule\\Controller\\Error',
    'action' => 'error401',
    'parameter' => array('message' => null, 'exception' => null)
);
$routes['error403'] = array(
    'controller' => '\\DefaultModule\\Controller\\Error',
    'action' => 'error403',
    'parameter' => array('message' => null, 'exception' => null)
);
$routes['error404'] = array(
    'controller' => '\\DefaultModule\\Controller\\Error',
    'action' => 'error404',
    'parameter' => array('message' => null, 'exception' => null)
);
$routes['error500'] = array(
    'controller' => '\\DefaultModule\\Controller\\Error',
    'action' => 'error500',
    'parameter' => array('message' => null, 'exception' => null)
);