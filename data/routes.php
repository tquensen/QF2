<?php
//controllers are loaded from the container by 'service', then method 'action' is called with the parameters as first parameter
//the controller object/service is loaded from the DI container (see dependencies.php for example)

$routes['home'] = array(
    'url' => 'home',
    'service' => 'examplemodule.controller.base',
    'action' => 'homeAction'
);

/* examples
$routes['project.show'] = array(
    'url' => 'projects/:project:',
    'service' => 'examplemodule.controller.project',
    'action' => 'showProjectAction'
);
*/


//configure routes (set template, change rights
//$routes['some.route']['parameter']['_template'] = 'alternative_template';
//$routes['some.other.route']['rights'] = 'admin';