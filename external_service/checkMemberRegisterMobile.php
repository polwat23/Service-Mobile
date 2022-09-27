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

$lang_locale = "th";

$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);
$jsonConfigError = file_get_contents(__DIR__.'/../config/config_indicates_error.json');
$configError = json_decode($jsonConfigError,true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$fetchUserAccount = $conmysql->prepare("SELECT MEMBER_NO,PHONE_NUMBER,EMAIL,REGISTER_DATE,ACCOUNT_STATUS FROM gcmemberaccount WHERE member_no = :member_no");
	$fetchUserAccount->execute([':member_no' => $dataComing["member_no"]]);
	$arrayGroup = array();
	if($fetchUserAccount->rowCount() > 0){
		while($rowUserRegis = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$arrayGroup["MEMBER_NO"] = $rowUserRegis["MEMBER_NO"];
			$arrayGroup["MEMBER_DATE"] = $lib->convertdate($rowUserRegis["REGISTER_DATE"],'D m Y');
			$arrayGroup["TEL"] = $rowUserRegis["PHONE_NUMBER"];
			$arrayGroup["EMAIL"] = $rowUserRegis["EMAIL"] ?? "-";
			$arrayGroup["ACCOUNT_STATUS"] = $rowUserRegis["ACCOUNT_STATUS"];
		}

		$arrayResult["DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult["MESSAGE"] = "Not found membership";
		$arrayResult["RESULT"] = FALSE;
		echo json_encode($arrayResult);
	}
}else{
	http_response_code(500);
}
?>