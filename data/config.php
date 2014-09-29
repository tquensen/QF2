<?php
$config['meta']['website_title'] = 'QF Website';
$config['meta']['meta_description'] = 'Default meta description.';

$config['theme'] = ''; //no theme
$config['template'] = ''; //empty string/null = 'default' template; false for no template
$config['default_format'] = 'html';

$config['template_path'] = __DIR__.'/../templates';
$config['web_path'] = __DIR__.'/../web';

$config['meta']['titleAlign'] = 'rtl'; //or ltr
$config['meta']['useCachebuster'] = true; //append ?timestamp to js/css urls)

//$config['meta']['css']['myCssFile'] = array('path/filename', 'module', 'media');
//$config['meta']['css']['myOtherCssFile'] = array('css/styles.css', null, 'screen');

//$config['meta']['js']['myJsFile'] = array('path/filename', 'module', 'type (default=text/javascript)');
//$config['meta']['js']['jquery'] = array('js/jquery.min.js');

$config['home_route'] = 'home';
$config['base_url'] = '/'; //http://example.com/;
//$config['base_url_i18n'] = '/'; //http://:lang:.example.com/;
//$config['static_url'] = 'http://static.example.com/';


//user roles
// $config['roles']['ROLE_NAME'] = array('list','of','rights')
$config['roles']['GUEST'] = array('guest', 'all');
$config['roles']['USER'] = array('user', 'all');
$config['roles']['ADMIN'] = array('admin', 'user', 'all');

$config['security']['secureDefault'] = false; //deny access if no 'rights' are configured on the route?

//i18n
$config['languages'] = array('en', 'de');
$config['default_language'] = $config['languages'][0];
//fallback for current_language
$config['current_language'] = $config['default_language'];


//database
$config['db']['default'] = array(
    'driver' => 'mysql:host=localhost;dbname=qfdb', //A valid PDO dsn. @see http://php.net/manual/de/pdo.construct.php
    'username' => 'root', //The user name for the DSN string. This parameter is optional for some PDO drivers.
    'password' => '', //The password for the DSN string. This parameter is optional for some PDO drivers.
    'options' => array() //A key=>value array of driver-specific connection options. (optional)
);

//database (mongodb)
$config['mongo']['default'] = array(
    'server' => 'mongodb://localhost:27017', //@see http://php.net/manual/de/mongoclient.construct.php
    'database' => 'qfdb',
    'options' => array( //A key=>value array of driver-specific connection options. (optional)
        'connect' => true,
        'w' => 1,
        
        //'wTimeoutMS' => 10000
        //'socketTimeoutMS' => 30000,
        //'connectTimeoutMS' => 60000
    ),
    'driverOptions' => array()
);
