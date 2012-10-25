<?php

$json = array(
    'success' => $success,
    'message' => $message,
    'url' => $qf->getUrl('example.index')
);

echo json_encode($json);