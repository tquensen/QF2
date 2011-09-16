<?php
$config = array();

$config['website_title'] = 'QF Website';
$config['meta_description'] = 'Default meta description.';


$config['theme'] = ''; //no theme
$config['template'] = 'default';
$config['default_format'] = 'html';


$config['home_route'] = 'home';
$config['base_url'] = '/'; //http://example.com;
//$qf_config['static_url'] = 'http://static.example.com/';

$config['error404_route'] = 'error404';


//some fallback values
$config['current_route'] = $config['current_module'] = $config['current_page'] = false;

//i18n
$config['languages'] = array('en', 'de');
$config['default_language'] = $config['languages'][0];

//fallback for current_language
$config['current_language'] = $config['default_language'];


$config['roles'] = array(
    'guest' => array('GUEST'),
    'user' => array('USER'),
    'admin' => array('ADMIN', 'USER')
);

//database connection
/*
$config['db']['default'] = array(
    'driver' => 'mysql:host=localhost;dbname=qfdb', //A valid PDO dsn. @see http://de3.php.net/manual/de/pdo.construct.php
    'username' => 'root', //The user name for the DSN string. This parameter is optional for some PDO drivers.
    'password' => '', //The password for the DSN string. This parameter is optional for some PDO drivers.
    'options' => array() //A key=>value array of driver-specific connection options. (optional)
);
 */

//add module config
//.. either as subkey
//$config['exampleModule'] = $this->load(__DIR__.'/../modules/ExampleModule/data/config.php');
//.. or merge with global config
//$this->merge(__DIR__.'/../modules/ExampleModule/data/config.php', $config);

//import other config files
//.. as subkeys
$config['routes'] = $this->load(__DIR__.'/routes.php');
$config['tasks'] = $this->load(__DIR__.'/tasks.php');
//.. merge
//$this->merge(__DIR__.'/additionalConfig.php', $config);


return $config;