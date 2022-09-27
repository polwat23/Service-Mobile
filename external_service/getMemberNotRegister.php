<?php
ini_set('display_errors', false);
ini_set('error_log', __DIR__.'/../log/external_error.log');
header("Access-Control-Allow-Methods: POST");
header("Content-type: application/json;charset=utf8");

require_once(__DIR__.'/../extension/vendor/autoload.php');
require_once(__DIR__.'/../include/connection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/validate_input.php');


use Utility\library;
use Connection\connection;

$con = new connection();
$lib = new library();
$conmysql = $con->connecttomysql();
$conoracle = $con->connecttooracle();

$lang_locale = "th";

$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);
$jsonConfigError = file_get_contents(__DIR__.'/../config/config_indicates_error.json');
$configError = json_decode($jsonConfigError,true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$arrayUserRegister = array();
	$fetchUserAccount = $conmysql->prepare("SELECT member_no FROM gcmemberaccount");
	$fetchUserAccount->execute();
	while($rowUserRegis = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
		$arrayUserRegister[] = "'".$rowUserRegis["member_no"]."'";
	}
	$arrayGroup = array();
	$fetchUserNotRegis = $conoracle->prepare("SELECT mb.member_no,mp.prename_desc,mb.memb_name,mb.memb_surname,mb.member_date
											,mb.mem_telmobile as MEM_TELMOBILE,mb.email_address as email 
											FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											WHERE mb.resign_status = '0' and mb.member_no NOT IN(".implode(",",$arrayUserRegister).")");
	$fetchUserNotRegis->execute();
	while($rowUserNotRegis = $fetchUserNotRegis->fetch(PDO::FETCH_ASSOC)){
		$arrayUserNotRegister = array();
		$arrayUserNotRegister["MEMBER_NO"] = $rowUserNotRegis["MEMBER_NO"];
		$arrayUserNotRegister["NAME"] = $rowUserNotRegis["PRENAME_DESC"].$rowUserNotRegis["MEMB_NAME"]." ".$rowUserNotRegis["MEMB_SURNAME"];
		$arrayUserNotRegister["MEMBER_DATE"] = $lib->convertdate($rowUserNotRegis["MEMBER_DATE"],'D m Y');
		$arrayUserNotRegister["TEL"] = $rowUserNotRegis["MEM_TELMOBILE"];
		$arrayUserNotRegister["EMAIL"] = $rowUserNotRegis["EMAIL"] ?? "-";
		$arrayGroup[] = $arrayUserNotRegister;
	}
	$arrayResult["USER_NOT_REGISTER"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
}else{
	http_response_code(500);
}
?>