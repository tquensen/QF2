<?php
//define module specific services (controller, tasks, eventlisteners, ..)

//for the most basic controller-service, extend \QF\Controller and inject the container,
//then call $this->getService('service') to access your dependencies
//create getter-functions like protected function getI18n() { return $this->getService('i18n') } for cleaner code
/*
$c['examplemodule.controller.default'] = function ($c) {
    $controller = new \ExampleModule\Controller\Basic(); //extends \QF\Controller
    $controller->setContainer($c);
    
    return $controller;
};
*/

$c['examplemodule.controller.base'] = function ($c) {
    $controller = new \ExampleModule\Controller\Base();
    $controller->setQf($c['core']);
    $controller->setView($c['view']);
    $controller->setI18n($c['i18n']);
    $controller->setMeta($c['meta']);
    
    return $controller;
};

$c['examplemodule.controller.example'] = function ($c) {
    $controller = new \ExampleModule\Controller\Example();
    $controller->setQf($c['core']);
    $controller->setView($c['view']);
    $controller->setI18n($c['i18n']);
    $controller->setMeta($c['meta']);
    $controller->setDb($c['db']); 
    $controller->setSecurity($c['security']);
    return $controller;
};

$c['examplemodule.task.example'] = function ($c) {
    $controller = new \ExampleModule\Task\Example();
    $controller->setCli($c['cli']);
    
    return $controller;
};

