<?php
$Json_Request = file_get_contents('php://input');
$jsonData = json_decode($Json_Request, TRUE);

file_put_contents(__DIR__.'/../../log/log_error.txt', json_encode($jsonData) . PHP_EOL, FILE_APPEND);
?>