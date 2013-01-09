<?php
define('QF_CLI', isset($_SERVER['argc']) && $_SERVER['argc'] > 1 ? true : false);
define('QF_DEBUG', QF_CLI || QF_ENV === 'dev' ? true : false);

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', QF_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

//initialize PSR-0 compatible autooader
require_once(__DIR__.'/lib/Symfony/Component/ClassLoader/UniversalClassLoader.php');
$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
//autoload all namespaced classes inside the lib and modules folder
$loader->registerNamespace('QF', __DIR__.'/lib');
$loader->registerNamespaceFallbacks(array(__DIR__.'/lib', __DIR__.'/modules'));
//autoload all classes with PEAR-like class names inside the lib folder
$loader->registerPrefix('Pimple', __DIR__.'/lib/Pimple');
$loader->registerPrefixFallbacks(array(__DIR__.'/lib'));
$loader->register();

//require_once(__DIR__.'/lib/functions.php');

//load config
require __DIR__.'/data/config.php';

//define and initialize dependencies
require __DIR__.'/dependencies.php';
