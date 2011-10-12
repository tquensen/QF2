#!/usr/bin/php
<?php
//allow only cli access
if (isset($_SERVER['REMOTE_ADDR']) || !isset($_SERVER['argc']) || $_SERVER['argc'] < 2) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

define('QF_ENV', 'cli');

require_once __DIR__.'/bootstrap.php';

try {

    $taskData = $qf->cli->parseArgs($argv);    
    $output = $cli->callTask($taskData['class'], $taskData['task'], $taskData['parameter']);
    echo $output ? $output : '[no output]'."\n";
    
} catch (Exception $e) {
    echo $e;
}