<?php
require_once(__DIR__.'/../include/validate_input.php');

$txt = "Member_no ".$dataComing["member_no"]." | DeepLink -> ".$dataComing["url_deeplink"]." | ".date('Y-m-d H:i:s');

file_put_contents(__DIR__.'/../log/deepLink.txt', $txt. PHP_EOL, FILE_APPEND);
?>