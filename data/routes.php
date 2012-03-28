<?php
$config['routes']['home'] = array(
    'url' => 'home',
    'controller' => '\\ExampleModule\\Controller\\Base',
    'action' => 'home'
);

/* examples
$config['routes']['projects'] = array(
    'url' => 'projects/:project:',
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'projects'
);
*/

//load error routes
$this->load(__DIR__.'/../modules/DefaultModule/data/errorRoutes.php');

//load routes from Example module
$this->load(__DIR__.'/../modules/ExampleModule/data/routes.php');
