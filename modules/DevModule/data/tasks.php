<?php
$tasks['db.install'] = array(
    'class' => '\\DevModule\\Task\\Installer',
    'task' => 'install',
    'parameter' => array(
        'entity' => false,
        'from' => 0,
        'to' => 0,
        'storageKey' => 'db' //how to access the db connection ($c['db'])
    ),
    'assign' => array('entity', 'from', 'to')
);

$tasks['db.uninstall'] = array(
    'class' => '\\DevModule\\Task\\Installer',
    'task' => 'uninstall',
    'parameter' => array(
        'entity' => false,
        'from' => 0,
        'to' => 0,
        'storageKey' => 'db' //how to access the db connection ($c['db'])
    ),
    'assign' => array('entity', 'from', 'to')
);

$tasks['mongo.install'] = array(
    'class' => '\\DevModule\\Task\\Installer',
    'task' => 'installMongo',
    'parameter' => array(
        'entity' => false,
        'from' => 0,
        'to' => 0,
        'storageKey' => 'db' //how to access the db connection ($c['db'])
    ),
    'assign' => array('entity', 'from', 'to')
);

$tasks['mongo.uninstall'] = array(
    'class' => '\\DevModule\\Task\\Installer',
    'task' => 'uninstall',
    'parameter' => array(
        'entity' => false,
        'from' => 0,
        'to' => 0,
        'storageKey' => 'db' //how to access the db connection ($c['db'])
    ),
    'assign' => array('entity', 'from', 'to')
);

$tasks['assets.link'] = array(
    'class' => '\\DevModule\\Task\\Assets',
    'task' => 'link'
);