<?php
$routes = array();

$routes['home'] = array(
    'url' => 'home',
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'home'
);

/* examples
$routes['projects'] = array(
    'url' => 'projects/:project:',
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'projects'
);
*/

//load error routes
$this->merge(__DIR__.'/../modules/DefaultModule/data/errorRoutes.php', $routes);


//load routes from Example module
$this->merge(__DIR__.'/../modules/ExampleModule/data/routes.php', $routes);



return $routes;