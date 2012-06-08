<?php
$config['tasks']['dev.install'] = array(
    'class' => '\\DevModule\\Task\\Install',
    'task' => 'install',
    'parameter' => array(
        'entity' => false,
        'from' => 0,
        'to' => 0,
        'type' => 'install',
        'storageKey' => 'db' //how to access the db connection ($qf->db)
    ),
    'assign' => array('entity', 'from', 'to')
);

$config['tasks']['dev.uninstall'] = array(
    'class' => '\\DevModule\\Task\\Install',
    'task' => 'install',
    'parameter' => array(
        'entity' => false,
        'from' => 0,
        'to' => 0,
        'type' => 'uninstall',
        'storageKey' => 'db' //how to access the db connection ($qf->db)
    ),
    'assign' => array('entity', 'from', 'to')
);