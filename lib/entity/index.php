<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain');
require_once(__DIR__.'/Entity.php');
require_once(__DIR__.'/TestEntity.php');

$a = new TestEntity;
$a->property = 'FOOBAR';
$a->id = 27;
$a->property2 = 'Banane';
foreach ($a as $k => $v) {
    echo $k.': ';
    var_dump($v);
}
unset($a->property2);
unset($a->id);
foreach ($a as $k => $v) {
    echo $k.': ';
    var_dump($v);
}
$b = new TestEntity;
$b->id = 3;
$b->property2 = 'WOOT';

$c = new TestEntity;
$c->id = 4;
$c->addChildren($b);

$a->addChildren($b);
$a->addChildren($c);

foreach ($a as $k => $v) {
    echo $k.': ';
    var_dump($v);
}

var_dump($a->toArray());

var_dump($a->hasId());

var_dump($a->hasProperty2());

var_dump($a->hasChildren());

var_dump($b->hasChildren());


var_dump($a['children'][3]);
var_dump($a->children[3]);

$a->property2 = 221;
var_dump($a->property2);