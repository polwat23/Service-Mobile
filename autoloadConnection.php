<?php
date_default_timezone_set("Asia/Bangkok");

require_once(__DIR__.'/include/connection.php');
require_once(__DIR__.'/include/validate_input.php');

use Connection\connection;

$con = new connection();
$conmysql = $con->connecttomysql();
$conoracle = $con->connecttooracle();
?>