<?php
$config['website_title'] = 'QF Website';
$config['meta_description'] = 'Default meta description.';


$config['theme'] = ''; //no theme
$config['template'] = 'default';
$config['default_format'] = 'html';


$config['home_route'] = 'home';
$config['base_url'] = '/'; //http://example.com;
//$qf_config['static_url'] = 'http://static.example.com/';

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

//import config files
//syntax:
//$this->load->(file)
//where file is the full path to the file to include

//import routes, task and any other general config files
$this->load(__DIR__.'/routes.php');
$this->load(__DIR__.'/tasks.php');

//add module config files
//$this->load(__DIR__.'/../modules/ExampleModule/data/config.php');

//load environment specific config
if (QF_DEBUG === true) {
    $this->load(__DIR__.'/config_dev.php');
} else {
    $this->load(__DIR__.'/config_prod.php');
}
