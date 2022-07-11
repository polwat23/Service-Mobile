<?php

require_once(__DIR__.'/../../include/connection.php');
use Connection\connection;

$con = new connection();
$conmysql = $con->connecttomysql();
$checkSystem = $conmysql->prepare("SELECT *FROM gcmemberaccount WHERE line_token ='U0e9f26a43992b856ed5561ffb75d049f'");
$checkSystem->execute();
$rowcCeckSystem = $checkSystem->fetch(PDO::FETCH_ASSOC)

echo $_POST["id"];

?>