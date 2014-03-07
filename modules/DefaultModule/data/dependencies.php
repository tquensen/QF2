<?php
//define module specific services (controller, tasks, eventlisteners, ..)

$c['defaultmodule.controller.error'] = function ($c) {
    $controller = new \DefaultModule\Controller\Error();
    $controller->setQf($c['core']);
    $controller->setView($c['view']);
    $controller->setI18n($c['i18n']);
    $controller->setMeta($c['meta']);
    
    return $controller;
};