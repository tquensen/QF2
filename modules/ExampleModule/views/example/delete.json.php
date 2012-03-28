<?php

$json = array(
    'success' => $success,
    'message' => $message,
    'url' => $qf->routing->getUrl('example.index')
);

echo json_encode($json);