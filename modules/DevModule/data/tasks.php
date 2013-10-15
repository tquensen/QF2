<?php
$tasks['db.install'] = array(
    'service' => 'devmodule.task.installer',
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
    'service' => 'devmodule.task.installer',
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
    'service' => 'devmodule.task.installer',
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
    'service' => 'devmodule.task.installer',
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
    'service' => 'devmodule.task.assets',
    'task' => 'link'
);