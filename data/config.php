<?php
$config['qf']['parameter']['website_title'] = 'QF Website';
$config['qf']['parameter']['meta_description'] = 'Default meta description.';

$config['qf']['theme'] = ''; //no theme
$config['qf']['template'] = 'default';
$config['qf']['default_format'] = 'html';


$config['qf']['home_route'] = 'home';
$config['qf']['base_url'] = '/'; //http://example.com/;
//$config['qf']['base_url_i18n'] = '/'; //http://:lang:.example.com/;
//$config['qf']['static_url'] = 'http://static.example.com/';


//user roles
// $config['roles']['ROLE_NAME'] = array('list','of','rights')
$config['roles']['GUEST'] = array('guest');
$config['roles']['USER'] = array('user');
$config['roles']['ADMIN'] = array('admin', 'user');


//i18n
$config['i18n']['languages'] = array('en', 'de');
$config['i18n']['default_language'] = $config['i18n']['languages'][0];
//fallback for current_language
$config['i18n']['current_language'] = $config['i18n']['default_language'];


//database
$config['db']['default'] = array(
    'driver' => 'mysql:host=localhost;dbname=qfdb', //A valid PDO dsn. @see http://de3.php.net/manual/de/pdo.construct.php
    'username' => 'root', //The user name for the DSN string. This parameter is optional for some PDO drivers.
    'password' => '', //The password for the DSN string. This parameter is optional for some PDO drivers.
    'options' => array() //A key=>value array of driver-specific connection options. (optional)
);


//add module config files
//require __DIR__.'/../modules/ExampleModule/data/config.php';


//load environment specific config
if (file_exists(__DIR__.'/config_'.QF_ENV.'.php')) {
    require __DIR__.'/config_'.QF_ENV.'.php';
}