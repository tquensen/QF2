<?php

$json = array(
    'success' => true,
    'currentPage' => $pager->getPage(),
    'currentEntries' => $pager->getPageEntries(),
    'numPages' => $pager->getPages(),
    'numEntries' => $pager->getNumEntries(),
    'entities' => array()
);
if (count($entities)) {
    foreach ($entities as $entity) {
        //$json['entities'][] = $entity->toArray(); //return the complete entry

        //... or just specific and/or additional properties
        $json['entities'][] = array(
            'id' => $entity->id,
            'title' => $entity->title,
            'url' => $qf->getUrl('example.show', array('id' => $entity->id))
        );
    }
}

echo json_encode($json);