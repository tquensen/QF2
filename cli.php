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

define('QF_BASEPATH', dirname(__FILE__).'/');
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__).'/lib' . PATH_SEPARATOR . dirname(__FILE__).'/modules');

try {

    require_once(QF_BASEPATH.'lib/SplClassLoader.php');
    //require_once(QF_BASEPATH.'lib/functions.php');

    require_once('SplClassLoader/SplClassLoader.php');
    $classLoader = new SplClassLoader();
    $classLoader->register();   

    //configuration
    $config = new QF\Config(QF_BASEPATH.'data/config.php');

    //routing
    $qf = new QF\Core($config); // or new qfCoreI18n($config); to add i18n-capability to getUrl/redirectRoute methods
    
    //database
    /*
    $qf->db = new QF\DB($qf);
    */
    
    $cli = new QF\Cli($qf);
    $taskData = $cli->parseArgs($argv);
    if ($taskData) {
        if ($output = $cli->callTask($taskData['controller'], $taskData['task'], $taskData['parameter'])) {
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