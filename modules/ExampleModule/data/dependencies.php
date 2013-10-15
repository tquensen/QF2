<?php
//define module specific services (controller, tasks, eventlisteners, ..)

$c['examplemodule.controller.base'] = $c->share(function ($c) {
    $controller = new \ExampleModule\Controller\Base();
    $controller->setQf($c['core']);
    $controller->setView($c['view']);
    $controller->setI18n($c['i18n']);
    $controller->setMeta($c['meta']);
    
    return $controller;
});

$c['examplemodule.controller.example'] = $c->share(function ($c) {
    $controller = new \ExampleModule\Controller\Example();
    $controller->setQf($c['core']);
    $controller->setView($c['view']);
    $controller->setI18n($c['i18n']);
    $controller->setMeta($c['meta']);
    $controller->setDb($c['db']); 
    $controller->setSecurity($c['security']);
    return $controller;
});

$c['examplemodule.task.example'] = $c->share(function ($c) {
    $controller = new \ExampleModule\Task\Example();
    $controller->setCli($c['cli']);
    
    return $controller;
});
