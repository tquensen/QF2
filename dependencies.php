<?php

$c = new Pimple();
$c['config'] = $config;

//initialize QF\Core class
$c['core'] = $c->share(function ($c) {
    $config = $c['config'];

    $routes = array();
    require __DIR__.'/data/routes.php';
    
    $qf = new QF\Core($routes);
    
    $qf->setContainer($c);
    
    $qf->setRoutes($routes);
    if (!empty($config['home_route'])) { $qf->setHomeRoute($config['home_route']); }
    
    //i18n (optional)
    $qf->setI18n($c['i18n']);
    
    //security/user/session
    $qf->setSecurity($c['security']);
    
    return $qf;
});

//initialize QF\ViewManager class
$c['view'] = $c->share(function ($c) {
    $config = $c['config'];
    
    $view = new QF\ViewManager();
    
    $view->setContainer($c);
    
    if (!empty($config['theme'])) { $view->setTheme($config['theme']); }
    if (!empty($config['template'])) { $view->setTemplate($config['template']); }
    if (!empty($config['format'])) { $view->setFormat($config['format']); }
    if (!empty($config['default_format'])) { $view->setDefaultFormat($config['default_format']); }
    if (!empty($config['base_url'])) { $view->setBaseUrl($config['base_url']); }
    if (!empty($config['base_url_i18n'])) { $view->setBaseUrlI18n($config['base_url_i18n']); }
    if (!empty($config['static_url'])) { $view->setStaticUrl($config['static_url']); }
    if (!empty($config['template_path'])) { $view->setTemplatePath($config['template_path']); }
    if (!empty($config['module_path'])) { $view->setModulePath($config['module_path']); }
    if (!empty($config['web_path'])) { $view->setWebPath($config['web_path']); }
    
    //i18n (optional)
    $view->setI18n($c['i18n']);
    
    return $view;
});

//event dispatcher
$c['event'] = $c->share(function ($c) {  
    $config = $c['config'];
    return new QF\EventDispatcher($c, !empty($config['events']) ? $config['events'] : array());
});

$c['cli'] = $c->share(function ($c) {
    $config = $c['config'];
    
    $tasks = array();
    require __DIR__.'/data/tasks.php';
    
    $cli = new QF\Cli($tasks);
    
    $cli->setContainer($c);
    
    return $cli;
});

$c['security'] = $c->share(function ($c) {
    return new QF\Security($c['config']['roles']);
});

//i18n
$c['i18n'] = $c->share(function ($c) {  
    $config = $c['config'];
    return new QF\I18n($config['i18n_path'], $config['module_path'], $config['languages'], $config['default_language']);
});

//default translations
$c['t'] = $c->share(function ($c) {    
    return $c['i18n']->get();
});

//init database
/* PDO
$c['db'] = $c->share(function ($c) {
    $config = $c['config']; 
    return new QF\DB\DB($config['db']['default']);
});
*/   
/* mongoDB
$c['db'] = $c->share(function ($c) {
    $config = $c['config'];
    return new QF\Mongo\DB($config['db']['mongo']);
});
*/


//example for an event listener
/*
$c['listener.foo'] = $c->share(function ($c) {
    $config = $c['config']; //if configuration is required
    return new \Foo\EventListener($config['foo.something']);
});
*/
