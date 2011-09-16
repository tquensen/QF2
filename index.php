<?php
error_reporting(E_ALL | E_STRICT);

define('QF_CLI', false);

//show errors only on localhost
if (in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))) {
    define('QF_DEBUG', true);
} else {
    define('QF_DEBUG', false);
}
ini_set('display_errors', QF_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

define('QF_BASEPATH', __DIR__);

try {

    require_once(QF_BASEPATH.'/lib/Symfony/Component/ClassLoader/UniversalClassLoader.php');
    $loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
    //autoload all namespaced classes inside the lib and modules folder
    $loader->registerNamespaceFallbacks(array(__DIR__.'/lib', __DIR__.'/modules'));
    //autoload all classes with PEAR-like class names inside the lib folder
    $loader->registerPrefixFallbacks(array(__DIR__.'/lib'));
    $loader->register();
    
    //require_once(QF_BASEPATH.'/lib/functions.php');

    //configuration
    $config = new QF\Config(QF_BASEPATH.'/data/config.php');
    $config->format = isset($_GET['format']) ? $_GET['format'] : null;

    $qf = new QF\Core($config); // or new qfCoreI18n($config); to add i18n-capability to getUrl/redirectRoute methods

    //routing
    $qf->routing = new QF\Routing($qf);
    $route = isset($_GET['route']) ? $_GET['route'] : '';
    $routeData = $qf->routing->parseRoute($route, true);
    
    //i18n
    $language = isset($_GET['language']) ? $_GET['language'] : '';
    $qf->i18n = new QF\I18n($qf, QF_BASEPATH . ' data/i18n',  $language);
    $qf->t = $qf->i18n->get();

    //set i18n title/description
    $qf->setConfig('website_title') = $qf->t->website_title;
    $qf->setConfig('meta_description') = $qf->t->meta_description;
    
    
    //database
    /*
    $qf->db = new QF\DB($qf);
    */
    
    
    
    //user handling
    session_name('your_session_name');
    session_start();
    $qf->user = new QF\User($qf);
    if (!empty($routeData['rights']) && !$qf->user->userHasRight($routeData['rights'])) {
        throw new QF\Exception\HttpException('permission denied', 403);
    }
    
    $pageContent = $qf->callAction($routeData['controller'], $routeData['action'], $routeData['parameter'], true);
    echo $qf->parseTemplate($pageContent);

} catch (Exception $e) {
    
    try {
        if ($e instanceof \QF\Exception\HttpException) {
            echo $qf->parseTemplate($qf->callError($e->getCode(), $e->getMessage(), $e));        
        } else {
            echo $qf->parseTemplate($qf->callError(500, '', $e)); 
        }
    } catch (Exception $e) {
        //seems like the error was inside the template or error page
        //display a fallback page
        require(QF_BASEPATH.'/web/error.php');  
    }     
}