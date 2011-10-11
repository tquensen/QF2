<?php
ini_set('display_errors', QF_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

define('QF_BASEPATH', __DIR__);

require_once(QF_BASEPATH.'/lib/Symfony/Component/ClassLoader/UniversalClassLoader.php');
$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
//autoload all namespaced classes inside the lib and modules folder
$loader->registerNamespace('QF', __DIR__.'/lib');
$loader->registerNamespaceFallbacks(array(__DIR__.'/lib', __DIR__.'/modules'));
//autoload all classes with PEAR-like class names inside the lib folder
$loader->registerPrefixFallbacks(array(__DIR__.'/lib'));
$loader->register();

//require_once(QF_BASEPATH.'/lib/functions.php');

//configuration
$config = new QF\Config(QF_BASEPATH.'/data/config.php');
$qf = new QF\Core($config);

//init database
/* PDO
$qf->db = new QF\DB\DB($config->db);
*/   
/* mongoDB
$qf->mongodb = new Mongo\DB($config->mongodb);
*/ 

if (QF_CLI === true) {
//cli

    chdir(__DIR__);
    
    //init routing (not required, but useful as it allows $qf->callRoute() and $qf->routing->getUrl() calls)
    $qf->routing = new QF\Routing($qf);

    //init cli
    $qf->cli = new QF\Cli($qf);
    
    $qf->setConfig('format', 'plain'); //use the plain format for views
    
    
    //init i18n with default language
    $qf->i18n = new QF\I18n($qf, QF_BASEPATH . '/data/i18n');
    $qf->t = $qf->i18n->get();
    
} else {
//web
    
    //init routing
    $qf->routing = new QF\Routing($qf);    

    //init i18n
    $language = isset($_GET['language']) ? $_GET['language'] : '';
    $qf->i18n = new QF\I18n($qf, QF_BASEPATH . '/data/i18n',  $language);
    $qf->t = $qf->i18n->get();

    //set i18n title/description
    $qf->setConfig('website_title') = $qf->t->website_title;
    $qf->setConfig('meta_description') = $qf->t->meta_description;    

    //user handling
    session_name('your_session_name');
    session_start();
    $qf->user = new QF\User($qf);
    
}