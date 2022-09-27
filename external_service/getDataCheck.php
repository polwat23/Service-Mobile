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
$conoracle = $con->connecttooracle();
$conoldoracle = $con->connecttooldoracle();

$lang_locale = "th";

$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);
$jsonConfigError = file_get_contents(__DIR__.'/../config/config_indicates_error.json');
$configError = json_decode($jsonConfigError,true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if($dataComing["action"] === 'tel'){
		if($dataComing["dsn"] === 'oracle'){
			$getTelFromNewOracle = $conoracle->prepare("SELECT TRIM(mem_telmobile) as MEM_TELMOBILE,MEMBER_NO FROM mbmembmaster");
			$getTelFromNewOracle->execute();
			while($rowTelNew = $getTelFromNewOracle->fetch(PDO::FETCH_ASSOC)){
				$getTeloldOracle = $conoldoracle->prepare("SELECT TRIM(mem_telmobile) as MEM_TELMOBILE FROM mbmembmaster where member_no = :member_no");
				$getTeloldOracle->execute([':member_no' => $rowTelNew["MEMBER_NO"]]);
				$rowTelOld = $getTeloldOracle->fetch(PDO::FETCH_ASSOC);
				if($rowTelNew["MEM_TELMOBILE"] !== $rowTelOld["MEM_TELMOBILE"]){
					$arrTel = array();
					$arrTel["TEL_NEW"] = $rowTelNew["MEM_TELMOBILE"];
					$arrTel["TEL_OLD"] = $rowTelOld["MEM_TELMOBILE"];
					$arrTel["MEMBER_NO"] = $rowTelNew["MEMBER_NO"];
					$arrayResult[] = $arrTel;
				}
			}
			$arrResponse["DATA"] = $arrayResult;
			echo json_encode($arrResponse);
		}else if($dataComing["dsn"] === 'mysql'){
			$getTelFromMysql = $conmysql->prepare("SELECT phone_number,member_no FROM gcmemberaccount");
			$getTelFromMysql->execute();
			while($rowTelNew = $getTelFromMysql->fetch(PDO::FETCH_ASSOC)){
				$getTeloldOracle = $conoldoracle->prepare("SELECT TRIM(mem_telmobile) as MEM_TELMOBILE FROM mbmembmaster where member_no = :member_no");
				$getTeloldOracle->execute([':member_no' => $rowTelNew["member_no"]]);
				$rowTelOld = $getTeloldOracle->fetch(PDO::FETCH_ASSOC);
				if($rowTelNew["phone_number"] != $rowTelOld["MEM_TELMOBILE"]){
					$arrTel = array();
					$arrTel["TEL_NEW"] = $rowTelNew["phone_number"];
					$arrTel["TEL_OLD"] = $rowTelOld["MEM_TELMOBILE"];
					$arrTel["MEMBER_NO"] = $rowTelNew["member_no"];
					$arrayResult[] = $arrTel;
				}
			}
			$arrResponse["DATA"] = $arrayResult;
			echo json_encode($arrResponse);
		}else{
			$arrResponse["DATA"] = [];
			echo json_encode($arrResponse);
		}
	}else if($dataComing["action"] === 'email'){
		if($dataComing["dsn"] === 'oracle'){
			$getMailFromNewOracle = $conoracle->prepare("SELECT TRIM(email) as EMAIL,MEMBER_NO FROM mbmembmaster");
			$getMailFromNewOracle->execute();
			while($rowMailNew = $getMailFromNewOracle->fetch(PDO::FETCH_ASSOC)){
				$getMailoldOracle = $conoldoracle->prepare("SELECT TRIM(email_address) as EMAIL_ADDRESS FROM mbmembmaster where member_no = :member_no");
				$getMailoldOracle->execute([':member_no' => $rowMailNew["MEMBER_NO"]]);
				$rowMailOld = $getMailoldOracle->fetch(PDO::FETCH_ASSOC);
				if($rowMailNew["EMAIL"] != $rowMailOld["EMAIL_ADDRESS"]){
					$arrMail = array();
					$arrMail["MAIL_NEW"] = $rowMailNew["EMAIL"];
					$arrMail["MAIL_OLD"] = $rowMailOld["EMAIL_ADDRESS"];
					$arrMail["MEMBER_NO"] = $rowMailNew["MEMBER_NO"];
					$arrayResult[] = $arrMail;
				}
			}
			$arrResponse["DATA"] = $arrayResult;
			echo json_encode($arrResponse);
		}else if($dataComing["dsn"] === 'mysql'){
			$getMailFromMysql = $conmysql->prepare("SELECT email,member_no FROM gcmemberaccount");
			$getMailFromMysql->execute();
			while($rowMailNew = $getMailFromMysql->fetch(PDO::FETCH_ASSOC)){
				$getMailoldOracle = $conoracle->prepare("SELECT TRIM(EMAIL) as EMAIL FROM mbmembmaster where member_no = :member_no");
				$getMailoldOracle->execute([':member_no' => $rowMailNew["member_no"]]);
				$rowMailOld = $getMailoldOracle->fetch(PDO::FETCH_ASSOC);
				if($rowMailNew["email"] != $rowMailOld["EMAIL"]){
					$arrMail = array();
					$arrMail["MAIL_NEW"] = $rowMailNew["email"];
					$arrMail["MAIL_OLD"] = $rowMailOld["EMAIL"];
					$arrMail["MEMBER_NO"] = $rowMailNew["member_no"];
					$arrayResult[] = $arrMail;
				}
			}
			$arrResponse["DATA"] = $arrayResult;
			echo json_encode($arrResponse);
		}else{
			$arrResponse["DATA"] = [];
			echo json_encode($arrResponse);
		}
	}else{
		$arrResponse["DATA"] = [];
		echo json_encode($arrResponse);
	}
}else{
	http_response_code(500);
}
?>