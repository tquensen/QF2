<?php

foreach ($c['modules'] as $module => $path) {
    if (file_exists($path.'/data/dependencies.php')) {
        require $path.'/data/dependencies.php';
    }
}

//load config
$c['config'] = $c->share(function ($c) {
    $config = array();

    foreach ($c['modules'] as $module => $path) {
        if (file_exists($path.'/data/config.php')) {
            require $path.'/data/config.php';
        }
        if (file_exists($path.'/data/config_'.QF_ENV.'.php')) {
            require $path.'/data/config'.QF_ENV.'.php';
        }
    }
    
    //load application config
    require __DIR__.'/config.php';
    
    //load environment specific config
    if (file_exists(__DIR__.'/config_'.QF_ENV.'.php')) {
        require __DIR__.'/config_'.QF_ENV.'.php';
    }
    
    return $config;
});

//load cli tasks
$c['tasks'] = $c->share(function ($c) {
    $tasks = array();

    foreach ($c['modules'] as $module => $path) {
        if (file_exists($path.'/data/tasks.php')) {
            require $path.'/data/tasks.php';
        }
    }
    //load application tasks
    require __DIR__.'/tasks.php';
    
    return $tasks;
});

//load routes
$c['routes'] = $c->share(function ($c) {
    $routes = array();
    
    foreach ($c['modules'] as $module => $path) {
        if (file_exists($path.'/data/routes.php')) {
            require $path.'/data/routes.php';
        }
    }
    
    //load application routes
    require __DIR__.'/routes.php';
    
    return $routes;
});

//load event configuration
$c['events'] = $c->share(function ($c) {
    $events = array();
    
    foreach ($c['modules'] as $module => $path) {
        if (file_exists($path.'/data/events.php')) {
            require $path.'/data/events.php';
        }
    }
    
    //load application events
    require __DIR__.'/events.php';
    
    return $events;
});

//initialize QF\Core class
$c['core'] = $c->share(function ($c) {
    $config = $c['config'];
    
    $qf = new QF\Core();
    
    $qf->setContainer($c);
    
    $qf->setRoutes($c['routes']);
    
    $qf->setView($c['view']);
    
    if (!empty($config['home_route'])) { $qf->setHomeRoute($config['home_route']); }
    
    //i18n (optional)
    $qf->setI18n($c['i18n']);
    
    //security/user/session
    $qf->setSecurity($c['security']);
    
    $qf->setEventDispatcher($c['event']);
    
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
    if (!empty($config['web_path'])) { $view->setWebPath($config['web_path']); }

    $view->setModules($c['modules']);

    //i18n (optional)
    $view->setI18n($c['i18n']);
    
    return $view;
});

//event dispatcher
$c['event'] = $c->share(function ($c) {  
    return new QF\EventDispatcher($c, $c['events']);
});

$c['cli'] = $c->share(function ($c) {
    $tasks = $c['tasks'];
    
    $cli = new QF\Cli($tasks);
    
    $cli->setContainer($c);
    
    return $cli;
});

$c['security'] = $c->share(function ($c) {
    $config = $c['config'];
    
    $security = new QF\Security($c['config']['roles']);
    
    if (!empty($config['security']['secureDefault'])) { $security->setSecureDefault($config['security']['secureDefault']); }
    
    $event = new Event($this);
    $c['event']->notify('security.init', $event);
    
    return $security;
});

//i18n
$c['i18n'] = $c->share(function ($c) {  
    $config = $c['config'];
    
    $translationDirectories = array();
    
    foreach ($c['modules'] as $module => $path) {
        if (file_exists($path.'/data/i18n')) {
            $translationDirectories[] = $path.'/data/i18n';
        }
    }
    
    $translationDirectories[] = __DIR__.'/i18n';
    
    return new QF\I18n($translationDirectories, $config['languages'], $config['default_language']);
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

