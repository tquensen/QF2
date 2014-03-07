<?php
$routes['error401'] = array(
    'service' => 'defaultmodule.controller.error',
    'action' => 'error401',
    'parameter' => array('message' => null, 'exception' => null),
    'rights' => 'all' //[['guest', 'user']] //guest OR user
);
$routes['error403'] = array(
    'service' => 'defaultmodule.controller.error',
    'action' => 'error403',
    'parameter' => array('message' => null, 'exception' => null),
    'rights' => 'all' //guest OR user
);
$routes['error404'] = array(
    'service' => 'defaultmodule.controller.error',
    'action' => 'error404',
    'parameter' => array('message' => null, 'exception' => null),
    'rights' => 'all' //guest OR user
);
$routes['error500'] = array(
    'service' => 'defaultmodule.controller.error',
    'action' => 'error500',
    'parameter' => array('message' => null, 'exception' => null),
    'rights' => 'all' //guest OR user
);
