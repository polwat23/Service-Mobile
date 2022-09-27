<?php
set_time_limit(100000);
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
$conoracle = $con->connecttooldoracle();

$lang_locale = "th";

$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);
$jsonConfigError = file_get_contents(__DIR__.'/../config/config_indicates_error.json');
$configError = json_decode($jsonConfigError,true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$fetchUserAccount = $conmysql->prepare("SELECT MEMBER_NO,EMAIL FROM gcmemberaccount");
	$fetchUserAccount->execute();
	$arrayGroup = array();
	$arrayMember = array();
	while($rowUserRegis = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
		$arrayUserRegister = array();
		$arrayUserRegister["MEMBER_NO"] = $rowUserRegis["MEMBER_NO"];
		$arrayUserRegister["EMAIL_MOBILE"] = $rowUserRegis["EMAIL"];
		
		$getEmail = $conoracle->prepare("SELECT TRIM(EMAIL) as EMAIL FROM mbmembmaster WHERE member_no = :member_no");
		$getEmail->execute([':member_no' => $rowUserRegis["MEMBER_NO"]]);
		$rowEmail = $getEmail->fetch(PDO::FETCH_ASSOC);
		if($rowEmail["EMAIL"] != $rowUserRegis["EMAIL"]){
			$arrayUserRegister["EMAIL_COOP"] = $rowEmail["EMAIL"];
			$arrayGroup[] = $arrayUserRegister;
		}
	}
	
	$arrayResult["EMAIL_NOTMATCH"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
}else{
	http_response_code(500);
}
?>