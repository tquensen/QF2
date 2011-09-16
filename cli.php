#!/usr/bin/php
<?php
ini_set('display_errors', '0');
error_reporting(E_ALL | E_STRICT);

//show errors only on localhost
if (isset($_SERVER['REMOTE_ADDR']) || !isset($_SERVER['argc']) || $_SERVER['argc'] < 2) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

define('QF_CLI', true);
define('QF_DEBUG', true);

ini_set('display_errors', '1');
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

    $qf = new QF\Core($config); 
    
    $qf->setConfig('format', 'text');
    
    //database
    /*
    $qf->db = new QF\DB($qf);
    */
    
    $cli = new QF\Cli($qf);
    $taskData = $cli->parseArgs($argv);
    if ($taskData) {
        if ($output = $cli->callTask($taskData['class'], $taskData['task'], $taskData['parameter'])) {
            echo $output;
        } else {
            '[no output]'."\n";
        }
    } else {
        '[invalid task]'."\n";
    }

} catch (Exception $e) {
    echo $e;
}