<?php

if ($success) {
    $json = array(
        'success' => true,
        'message' => $message,
        'url' => $qf->getUrl('example.show', array('id' => $entity->id)),
        'id' => $entity->id,
        'title' => $entity->title
    );
} else {
    $json = array_merge(array('success' => $form->isValid()), $form->toArray());
}

echo json_encode($json);