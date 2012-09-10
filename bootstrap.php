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

$c = new Pimple();

//configuration
$c['config'] = $c->share(function ($c) {
    return new QF\Config(QF_BASEPATH.'/data/config.php');
});

$c['routing'] = $c->share(function ($c) {
    require QF_BASEPATH.'/data/routes.php';
    return new QF\Routing($routes, $c['controller'], $c['user']);
});

$c['controller'] = $c->share(function ($c) {
    require QF_BASEPATH.'/data/config.php';
    return new QF\FrontController($config);
});

$c['cli'] = $c->share(function ($c) {
    require QF_BASEPATH.'/data/tasks.php';
    return new QF\Cli($tasks);
});

$c['user'] = $c->share(function ($c) {
    require QF_BASEPATH.'/data/roles.php';
    return new QF\User($roles);
});

//i18n
$c['i18n'] = $c->share(function ($c) {    
    $currentLanguage = $c['controller']->current_language;
    $defaultLanguage = $c['controller']->default_language ?: 'en';
    $languages = $c['controller']->languages ?: array();
    if (!$currentLanguage || !in_array($currentLanguage, $languages)) {
        $currentLanguage = $defaultLanguage;
        $c['controller']->current_language = $currentLanguage;
    }

    return new QF\I18n(QF_BASEPATH . '/data/i18n', $currentLanguage);
});

//default translations
$c['t'] = $c->share(function ($c) {    
    return $c['i18n']->get();
});

//init database
/* PDO
$c['db'] = $c->share(function ($c) {
    require QF_BASEPATH.'/data/db.php';
    return new QF\DB\DB($db['default']);
});
*/   
/* mongoDB
$c['db'] = $c->share(function ($c) {
    require QF_BASEPATH.'/data/db.php';
    return new QF\Mongo\DB($db['mongo']);
});
*/




if (QF_CLI === true) {
//cli
    
    chdir(__DIR__);
    $c['controller']->format = 'plain'; //use the plain format for views  
    
} else {
//web

    //init i18n
    $language = isset($_GET['language']) ? $_GET['language'] : '';
    $c['controller']->current_language = $language;
    
    //set i18n title/description
    $c['controller']->website_title = $c['t']->website_title;
    $c['controller']->meta_description = $c['t']->meta_description;    

    //user handling
    session_name('qf_session');
    session_start();
    
}