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
    
    if (!empty($config['theme'])) { $qf->setTheme($config['theme']); }
    if (!empty($config['template'])) { $qf->setTemplate($config['template']); }
    if (!empty($config['format'])) { $qf->setFormat($config['format']); }
    if (!empty($config['default_format'])) { $qf->setDefaultFormat($config['default_format']); }
    if (!empty($config['home_route'])) { $qf->setHomeRoute($config['home_route']); }
    if (!empty($config['base_url'])) { $qf->setBaseUrl($config['base_url']); }
    if (!empty($config['base_url_i18n'])) { $qf->setBaseUrlI18n($config['base_url_i18n']); }
    if (!empty($config['static_url'])) { $qf->setStaticUrl($config['static_url']); }
    if (!empty($config['template_path'])) { $qf->setTemplatePath($config['template_path']); }
    if (!empty($config['module_path'])) { $qf->setModulePath($config['module_path']); }
    if (!empty($config['web_path'])) { $qf->setWebPath($config['web_path']); }
    
    //i18n (optional)
    $qf->setI18n($c['i18n']);
    
    //user/session (optional)
    $qf->setUser($c['user']);
    
    return $qf;
});

$c['cli'] = $c->share(function ($c) {
    $config = $c['config'];
    
    $tasks = array();
    require __DIR__.'/data/tasks.php';
    
    return new QF\Cli($tasks);
});

$c['user'] = $c->share(function ($c) {
    return new QF\User($c['config']['roles']);
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
