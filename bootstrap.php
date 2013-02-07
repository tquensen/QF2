<?php
define('QF_CLI', isset($_SERVER['argc']) && $_SERVER['argc'] > 1 ? true : false);
define('QF_DEBUG', QF_CLI || QF_ENV === 'dev' ? true : false);

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', QF_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

//initialize composer autooader
$loader = require __DIR__.'vendor/autoload.php';
$loader->add('', array(__DIR__.'/lib', __DIR__.'/modules'));


//require_once(__DIR__.'/lib/functions.php');

//load config
require __DIR__.'/data/config.php';

//define and initialize dependencies
require __DIR__.'/dependencies.php';
