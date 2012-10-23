<?php
define('QF_CLI', isset($_SERVER['argc']) && $_SERVER['argc'] > 1 ? true : false);
define('QF_DEBUG', QF_CLI || QF_ENV === 'dev' ? true : false);

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', QF_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

//initialize PSR-0 compatible autooader
require_once(__DIR__.'/lib/Symfony/Component/ClassLoader/UniversalClassLoader.php');
$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
//autoload all namespaced classes inside the lib and modules folder
$loader->registerNamespace('QF', __DIR__.'/lib');
$loader->registerNamespaceFallbacks(array(__DIR__.'/lib', __DIR__.'/modules'));
//autoload all classes with PEAR-like class names inside the lib folder
$loader->registerPrefix('Pimple', __DIR__.'/lib/Pimple');
$loader->registerPrefixFallbacks(array(__DIR__.'/lib'));
$loader->register();

//require_once(__DIR__.'/lib/functions.php');

//load config
require __DIR__.'/data/config.php';

//define and initialize dependencies
$c = new Pimple();
$c['config'] = $config;

//initialize QF\Core class
$c['core'] = $c->share(function ($c) {
    $config = $c['config'];

    $routes = array();
    require __DIR__.'/data/routes.php';
    
    $qf = new QF\Core($c, !empty($config['parameter']) ? $config['parameter'] : array(), $routes);
    
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
    
    return $qf;
});

$c['cli'] = $c->share(function ($c) {
    $config = $c['config'];
    
    $tasks = array();
    require __DIR__.'/data/tasks.php';
    
    return new QF\Cli($tasks);
});

$c['user'] = $c->share(function ($c) {
    //return null; //user/session is optional, return null to deactivate
    return new QF\User($c['config']['roles']);
});

//i18n
$c['i18n'] = $c->share(function ($c) {  
    //return null; //i18n is optional, return null to deactivate
    $config = $c['config'];
    return new QF\I18n($config['i18n_path'], $config['module_path'], $config['languages'], $config['current_language'], $config['default_language']);
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
