<?php
date_default_timezone_set("Asia/Bangkok");

require_once(__DIR__.'/include/connection.php');

use Connection\connection;

$con = new connection();
$conmysql = $con->connecttomysql();
$conmysql2 = $con->connecttomysql();
$conoracle = $con->connecttooracle();
?>