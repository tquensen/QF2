<?php echo json_encode(array('success' => false, 'errorCode' => 500, 'message' => $message, 'exception' => (string)$e));