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

require_once __DIR__.'/../bootstrap.php';

try {
    
    $route = isset($_GET['route']) ? $_GET['route'] : '';
    $language = isset($_GET['language']) ? $_GET['language'] : '';
    if ($language && !empty($c['i18n'])) {
        $c['i18n']->setCurrentLanguage($language);
    }
    //set i18n title/description as template parameter
    $c['core']->website_title = $c['t']->website_title;
    $c['core']->meta_description = $c['t']->meta_description;    

    //session for user handling
    session_name('qf_session');
    session_start();
        
    //throws 404 QF\Exception\HttpException for invalid routes
    $routeData = $c['core']->parseRoute($route);
    
    //redirect 301 if default language is present in url
    /*
    if ($language && $language ==  $c['i18n']->getDefaultLanguage()) {
        $c['core']->redirectRoute($routeData['route'], $routeData['parameter'], 301);
    }
    */
    $pageContent = $c['core']->callRoute($routeData['route'], $routeData['parameter'], true);
    echo $c['core']->parseTemplate($pageContent);

} catch (Exception $e) {    
    try {
        //401, 403, 404, 500 ...
        if ($e instanceof \QF\Exception\HttpException) {
            echo $c['core']->parseTemplate($c['core']->callError($e->getCode(), $e->getMessage(), $e));        
        } else {
            echo $c['core']->parseTemplate($c['core']->callError(500, 'server error', $e)); 
        }
    } catch (Exception $e) {
        //seems like the error was inside the template or error page
        //display a fallback page
        require(__DIR__.'/../templates/error.php');  
    }     
}