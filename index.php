<?php
//define your environment (dev, test, prod, whatever) 
//either by using different index.php files on different systems with a hardcoded value
//or by checking the $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_HOST'], getenv('qf_env') or other appropriate environment variables
//the following is an example, change it as needed:
if ((isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'localhost') || (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1')) {
    define('QF_ENV', 'dev');
} elseif (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] == '123.45.56.890' /* ip of test server */) {
    define('QF_ENV', 'test');    
} else {
    define('QF_ENV', 'prod');
}

require_once __DIR__.'/bootstrap.php';

try {
    
    $route = isset($_GET['route']) ? $_GET['route'] : '';
    
    //throws 404 QF\Exception\HttpException for invalid routes
    $routeData = $c['routing']->parseRoute($route);
    $pageContent = $c['routing']->callRoute($routeData['route'], $routeData['parameter'], true);
    echo $c['controller']->parseTemplate($pageContent);

} catch (Exception $e) {    
    try {
        //401, 403, 404, 500 ...
        if ($e instanceof \QF\Exception\HttpException) {
            echo $c['controller']->parseTemplate($c['routing']->callError($e->getCode(), $e->getMessage(), $e));        
        } else {
            echo $c['controller']->parseTemplate($c['routing']->callError(500, 'server error', $e)); 
        }
    } catch (Exception $e) {
        //seems like the error was inside the template or error page
        //display a fallback page
        require(QF_BASEPATH.'/templates/error.php');  
    }     
}