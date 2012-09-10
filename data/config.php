<?php
$config['website_title'] = 'QF Website';
$config['meta_description'] = 'Default meta description.';


$config['theme'] = ''; //no theme
$config['template'] = 'default';
$config['default_format'] = 'html';


$config['home_route'] = 'home';
$config['base_url'] = '/'; //http://example.com/;
//$qf_config['static_url'] = 'http://static.example.com/';

//i18n
$config['languages'] = array('en', 'de');
$config['default_language'] = $config['languages'][0];

//fallback for current_language
$config['current_language'] = $config['default_language'];

//add module config files
//require __DIR__.'/../modules/ExampleModule/data/config.php';

//load environment specific config
if (file_exists(__DIR__.'/config_'.QF_ENV.'.php')) {
    require __DIR__.'/config_'.QF_ENV.'.php';
}