<?php
//default static pages / fallback (this must be the LAST route!)
$config['routes']['static'] = array(
    'url' => ':page:(.:_format:)',
    'controller' => '\\ExampleModule\\Controller\\Example',
    'action' => 'staticPage',
    'parameter' => array('page' => false, '_format' => false),
    'patterns' => array('_format' => '(json|html)')
);