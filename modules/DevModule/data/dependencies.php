<?php
//define module specific services (controller, tasks, eventlisteners, ..)

$c['devmodule.task.installer'] = $c->share(function ($c) {
    $controller = new \DevModule\Task\Installer();
    $controller->setContainer($c);
    
    return $controller;
});

$c['devmodule.task.assets'] = $c->share(function ($c) {
    $controller = new \DevModule\Task\Assets();
    $controller->setView($c['view']);
    
    return $controller;
});