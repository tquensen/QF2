<?php
$config['parameter']['website_title'] = 'QF Website';
$config['parameter']['meta_description'] = 'Default meta description.';

$config['theme'] = ''; //no theme
$config['template'] = '';
$config['default_format'] = 'html';

$config['template_path'] = __DIR__.'/../templates';
$config['module_path'] = __DIR__.'/../modules';
$config['web_path'] = __DIR__.'/../web';
$config['i18n_path'] = __DIR__.'/i18n';

$config['home_route'] = 'home';
$config['base_url'] = '/'; //http://example.com/;
//$config['base_url_i18n'] = '/'; //http://:lang:.example.com/;
//$config['static_url'] = 'http://static.example.com/';


//user roles
// $config['roles']['ROLE_NAME'] = array('list','of','rights')
$config['roles']['GUEST'] = array('guest');
$config['roles']['USER'] = array('user');
$config['roles']['ADMIN'] = array('admin', 'user');


//i18n
$config['languages'] = array('en', 'de');
$config['default_language'] = $config['languages'][0];
//fallback for current_language
$config['current_language'] = $config['default_language'];


//database
$config['db']['default'] = array(
    'driver' => 'mysql:host=localhost;dbname=qfdb', //A valid PDO dsn. @see http://de3.php.net/manual/de/pdo.construct.php
    'username' => 'root', //The user name for the DSN string. This parameter is optional for some PDO drivers.
    'password' => '', //The password for the DSN string. This parameter is optional for some PDO drivers.
    'options' => array() //A key=>value array of driver-specific connection options. (optional)
);


//add module config files
//require $config['module_path'] . '/ExampleModule/data/config.php';


//load environment specific config
if (file_exists(__DIR__.'/config_'.QF_ENV.'.php')) {
    require __DIR__.'/config_'.QF_ENV.'.php';
}