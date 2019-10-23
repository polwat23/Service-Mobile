<?php
date_default_timezone_set("Asia/Bangkok");

require_once(__DIR__.'/include/connection.php');
require_once(__DIR__.'/include/validate_input.php');

use Connection\connection;

$con = new connection();
$basetest = json_decode(isset($_SERVER["HTTP_BASETEST"]) ? $_SERVER["HTTP_BASETEST"] : false);
$conmysql = $con->connecttomysql($basetest);
$conoracle = $con->connecttooracle($basetest);
?>