<?php
ini_set('display_errors', false);
ini_set('error_log', __DIR__.'/../log/error_connect.log');

header('Content-Type: application/json;charset=utf-8');
date_default_timezone_set("Asia/Bangkok");

require_once(__DIR__.'/../include/connection.php');
require_once(__DIR__.'/../include/validate_input.php');

use Connection\connection;

$con = new connection();
$basetest = json_decode(isset($headers["basetest"]) ? $headers["basetest"] : false);
$conmysql = $con->connecttomysql($basetest);
$conoracle = $con->connecttooracle($basetest);
$conmongo = $con->connecttomongo($basetest);

// Test MariaDB
$testMariaDB = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE member_no = 'dev@mode'");
$testMariaDB->execute();
if($testMariaDB->rowCount() > 0){
	$arrResult["MYSQL"] = 'MYSQL is Connected 🟢';
}else{
	$arrResult["MYSQL"] = 'MYSQL is Disconnect 🟠';
}
$testOraclw = $conoracle->prepare("SELECT COOP_ID FROM cmucfcoopbranch");
$testOraclw->execute();
$rowtest = $testOraclw->fetch();
if(isset($rowtest["COOP_ID"])){
	$arrResult["ORACLE"] = 'ORACLE is Connected 🟢';
}else{
	$arrResult["ORACLE"] = 'ORACLE is Disconnect 🟠';
}

if($conmongo){
	$arrResult["MONGODB"] = 'MONGODB is Connected 🟢';
}else{
	$arrResult["MONGODB"] = 'MONGODB is Disconnect 🟠';
}
echo json_encode($arrResult);
?>