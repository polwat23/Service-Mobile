<?php
date_default_timezone_set("Asia/Bangkok");

require_once(__DIR__.'/include/connection.php');
require_once(__DIR__.'/include/validate_input.php');

use Connection\connection;

$con = new connection();
$header = apache_request_headers();
$basetest = json_decode(isset($header["basetest"]) ? $header["basetest"] : false);
$conmysql = $con->connecttomysql($basetest);
$conoracle = $con->connecttooracle($basetest);
?>