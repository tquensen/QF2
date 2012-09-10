<?php
define('QF_CLI', isset($_SERVER['argc']) && $_SERVER['argc'] > 1 ? true : false);
define('QF_DEBUG', QF_CLI || QF_ENV === 'dev' ? true : false);
define('QF_BASEPATH', __DIR__);

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', QF_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

require_once(QF_BASEPATH.'/lib/Symfony/Component/ClassLoader/UniversalClassLoader.php');
$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
//autoload all namespaced classes inside the lib and modules folder
$loader->registerNamespace('QF', __DIR__.'/lib');
$loader->registerNamespaceFallbacks(array(__DIR__.'/lib', __DIR__.'/modules'));
//autoload all classes with PEAR-like class names inside the lib folder
$loader->registerPrefix('Pimple', __DIR__.'/lib/Pimple');
$loader->registerPrefixFallbacks(array(__DIR__.'/lib'));
$loader->register();

//require_once(QF_BASEPATH.'/lib/functions.php');

require QF_BASEPATH.'/data/config.php';

$c = new Pimple();
foreach ($config as $k => $v) {
    $c['cfg_'.$k] = $v;
}

$c['controller'] = $c->share(function ($c) {
    $config = $c['cfg_controller'];
    $controller = new QF\FrontController(!empty($config['parameter']) ? $config['parameter'] : array());
    
    if (!empty($config['theme'])) { $controller->setTheme($config['theme']); }
    if (!empty($config['template'])) { $controller->setTemplate($config['template']); }
    if (!empty($config['format'])) { $controller->setFormat($config['format']); }
    if (!empty($config['default_format'])) { $controller->setDefaultFormat($config['default_format']); }
    
    return $controller;
});

$c['routing'] = $c->share(function ($c) {
    $routes = array();
    require QF_BASEPATH.'/data/routes.php';
    $config = $c['cfg_routing'];
    
    $routing = new QF\Routing($routes, $c['controller'], $c['user'], $c['i18n']);
    
    if (!empty($config['home_route'])) { $routing->setHomeRoute($config['home_route']); }
    if (!empty($config['base_url'])) { $routing->setBaseUrl($config['base_url']); }
    if (!empty($config['base_url_i18n'])) { $routing->setBaseUrlI18n($config['base_url_i18n']); }
    if (!empty($config['static_url'])) { $routing->setStaticUrl($config['static_url']); }
    
    return $routing;
});

$c['cli'] = $c->share(function ($c) {
    $tasks = array();
    require QF_BASEPATH.'/data/tasks.php';
    return new QF\Cli($tasks);
});

$c['user'] = $c->share(function ($c) {
    return new QF\User($c['cfg_roles']);
});

//i18n
$c['i18n'] = $c->share(function ($c) {  
    $config = $c['cfg_i18n'];
    return new QF\I18n(QF_BASEPATH . '/data/i18n', $config['languages'], $config['current_language'], $config['default_language']);
});

//default translations
$c['t'] = $c->share(function ($c) {    
    return $c['i18n']->get();
});

//init database
/* PDO
$c['db'] = $c->share(function ($c) {
    return new QF\DB\DB($c['cfg_db']['default']);
});
*/   
/* mongoDB
$c['db'] = $c->share(function ($c) {
    return new QF\Mongo\DB($c['cfg_db']['mongo']);
});
*/




if (QF_CLI === true) {
//cli
    
    chdir(__DIR__);
    $c['controller']->setFormat('plain'); //use the plain format for views  
    
} else {
//web

    //init i18n
    $language = isset($_GET['language']) ? $_GET['language'] : '';
    if ($language) {
        $c['i18n']->setCurrentLanguage($language);
    }
    
    //set i18n title/description as frontController parameter
    $c['controller']->website_title = $c['t']->website_title;
    $c['controller']->meta_description = $c['t']->meta_description;    

    //user handling
    session_name('qf_session');
    session_start();
    
}