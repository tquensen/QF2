<?php

error_reporting(E_ALL | E_STRICT);
define('QF_CLI', false);

//enable debug/dev mode for localhost
define('QF_DEBUG', in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1')) ? true : false);

require_once __DIR__.'/bootstrap.php';

try {
    
    $route = isset($_GET['route']) ? $_GET['route'] : '';
    
    //throws 404 QF\Exception\HttpException for invalid routes
    $routeData = $qf->routing->parseRoute($route);
    $pageContent = $qf->callRoute($routeData['route'], $routeData['parameter'], true);
    echo $qf->parseTemplate($pageContent);

} catch (Exception $e) {    
    try {
        //401, 403, 404, 500 ...
        if ($e instanceof \QF\Exception\HttpException) {
            echo $qf->parseTemplate($qf->callError($e->getCode(), $e->getMessage(), $e));        
        } else {
            echo $qf->parseTemplate($qf->callError(500, 'server error', $e)); 
        }
    } catch (Exception $e) {
        //seems like the error was inside the template or error page
        //display a fallback page
        require(QF_BASEPATH.'/web/error.php');  
    }     
}