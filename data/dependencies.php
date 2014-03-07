<?php

foreach ($c['modules'] as $module => $path) {
    if (file_exists($path.'/data/dependencies.php')) {
        require $path.'/data/dependencies.php';
    }
}

//load config
$c['config'] = function ($c) {
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
};

//load cli tasks
$c['tasks'] = function ($c) {
    $tasks = array();

    foreach ($c['modules'] as $module => $path) {
        if (file_exists($path.'/data/tasks.php')) {
            require $path.'/data/tasks.php';
        }
    }
    //load application tasks
    require __DIR__.'/tasks.php';
    
    return $tasks;
};

//load routes
$c['routes'] = function ($c) {
    $routes = array();
    
    foreach ($c['modules'] as $module => $path) {
        if (file_exists($path.'/data/routes.php')) {
            require $path.'/data/routes.php';
        }
    }
    
    //load application routes
    require __DIR__.'/routes.php';
    
    return $routes;
};

//load widget/slots configuration
$c['widgets'] =function ($c) {
    $slots = array();
    $widgets = array();
    
    foreach ($c['modules'] as $module => $path) {
        if (file_exists($path.'/data/widgets.php')) {
            require $path.'/data/widgets.php';
        }
    }
    
    //load application events
    require __DIR__.'/widgets.php';
    
    return $slots;
};

//load event configuration
$c['events'] = function ($c) {
    $events = array();
    
    foreach ($c['modules'] as $module => $path) {
        if (file_exists($path.'/data/events.php')) {
            require $path.'/data/events.php';
        }
    }
    
    //load application events
    require __DIR__.'/events.php';
    
    return $events;
};

//initialize QF\Core class
$c['core'] = function ($c) {
    $config = $c['config'];
    
    $qf = new QF\Core();
    
    $qf->setContainer($c);
    
    $qf->setRoutes($c['routes']);
    $qf->setWidgets($c['widgets']);
    $qf->setView($c['view']);
    
    if (!empty($config['home_route'])) { $qf->setHomeRoute($config['home_route']); }
    
    //i18n (optional)
    $qf->setI18n($c['i18n']);
    
    //security/user/session
    $qf->setSecurity($c['security']);
    
    $qf->setEventDispatcher($c['event']);
    
    return $qf;
};

//initialize QF\ViewManager class
$c['view'] = function ($c) {
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
};

//http meta/asset helper
$c['meta'] = function ($c) {
    $config = $c['config'];
    $t = $c['t'];
    
    $meta = new \QF\Utils\Meta();
    
    //set i18n title/description
    if ($t) {
        $meta->setWebsiteTitle($t->website_title);
        $meta->setDescription($t->meta_description);
    } else {
        //or default title/description
        $meta->setWebsiteTitle($config['meta']['website_title']);
        $meta->setDescription($config['meta']['meta_description']);
    }
    
    $cb = !empty($config['meta']['useCachebuster']);
    
    if (!empty($config['meta']['css'])) {
        foreach ($config['meta']['css'] as $css) {
            $meta->setCSS($c['view']->getAsset($css[0], isset($css[1]) ? $css[1] : null, $cb), isset($css[2]) ? $css[2] : null);
        }
    }
    
    if (!empty($config['meta']['js'])) {
        foreach ($config['meta']['js'] as $js) {
            $meta->setJS($c['view']->getAsset($js[0], isset($js[1]) ? $js[1] : null, $cb), isset($js[2]) ? $js[2] : null);
        }
    }
    
    if (!empty($config['meta']['titleAlign'])) { $meta->setTitleAlign($config['meta']['titleAlign']); }
    
    return $meta;
};

//event dispatcher
$c['event'] = function ($c) {  
    return new QF\EventDispatcher($c, $c['events']);
};

$c['cli'] = function ($c) {
    $tasks = $c['tasks'];
    
    $cli = new QF\Cli($tasks);
    
    $cli->setContainer($c);
    
    return $cli;
};

$c['security'] = function ($c) {
    $config = $c['config'];
    
    $security = new QF\Security($c['config']['roles']);
    
    if (!empty($config['security']['secureDefault'])) { $security->setSecureDefault($config['security']['secureDefault']); }
    
    $event = new Event($security);
    $c['event']->notify('security.init', $event);
    
    return $security;
};

//i18n
$c['i18n'] = function ($c) {  
    $config = $c['config'];
    
    $translationDirectories = array();
    
    foreach ($c['modules'] as $module => $path) {
        if (file_exists($path.'/data/i18n')) {
            $translationDirectories[] = $path.'/data/i18n';
        }
    }
    
    $translationDirectories[] = __DIR__.'/i18n';
    
    return new QF\I18n($translationDirectories, $config['languages'], $config['default_language']);
};

//default translations
$c['t'] = function ($c) {    
    return $c['i18n']->get();
};

//init database
/* PDO
$c['db'] = function ($c) {
    $config = $c['config']; 
    return new QF\DB\DB($config['db']['default']);
};
*/   
/* mongoDB
$c['db'] = function ($c) {
    $config = $c['config'];
    return new QF\Mongo\DB($config['db']['mongo']);
};
*/


//example for an event listener
/*
$c['listener.foo'] = $function ($c) {
    $config = $c['config']; //if configuration is required
    return new \Foo\EventListener($config['foo.something']);
};
*/

