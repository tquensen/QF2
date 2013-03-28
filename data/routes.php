<?php
$routes['home'] = array(
    'url' => 'home',
    'controller' => '\\ExampleModule\\Controller\\Base',
    'action' => 'home'
);

/* examples
$routes['projects'] = array(
    'url' => 'projects/:project:',
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'projects'
);
*/


//configure routes (set template, change rights
//$routes['some.route']['parameter']['_template'] = 'alternative_template';
//$routes['some.other.route']['rights'] = 'admin';