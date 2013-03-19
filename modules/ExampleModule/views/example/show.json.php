<?php

//return the complete object (be carefull with passwords or other secret data!)
/*
$json = array_merge(
    array(
        'success' => true,
        'url' => $qf->getUrl('example.show', array('id' => $entity->id))
    ),
    $entity->toArray() //be careful if the model has also a 'success' or 'url' property
);
*/

//.. or only specific properties of the current object
$json = array(
    'success' => true,
    'url' => $qf->getUrl('example.show', array('id' => $entity->id)),
    'id' => $entity->id,
    'title' => $entity->title
);

echo json_encode($json);