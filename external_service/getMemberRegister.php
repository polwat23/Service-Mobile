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

$lang_locale = "th";

$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);
$jsonConfigError = file_get_contents(__DIR__.'/../config/config_indicates_error.json');
$configError = json_decode($jsonConfigError,true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$fetchUserAccount = $conmysql->prepare("SELECT MEMBER_NO,PHONE_NUMBER,EMAIL,REGISTER_DATE,ACCOUNT_STATUS,DEPTACCOUNT_NO_REGIS FROM gcmemberaccount");
	$fetchUserAccount->execute();
	$arrayGroup = array();
	$arrayMember = array();
	while($rowUserRegis = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
		$arrayUserRegister = array();
		$arrayUserRegister["MEMBER_NO"] = $rowUserRegis["MEMBER_NO"];
		$arrayUserRegister["REGISTER_DATE"] = $lib->convertdate($rowUserRegis["REGISTER_DATE"],'D/n/Y');
		$arrayUserRegister["TEL"] = $rowUserRegis["PHONE_NUMBER"];
		$arrayUserRegister["REGISTER_BY_DEPTACCOUNT_NO"] = $rowUserRegis["DEPTACCOUNT_NO_REGIS"];
		$arrayUserRegister["EMAIL"] = $rowUserRegis["EMAIL"] ?? "-";
		$arrayUserRegister["ACCOUNT_STATUS"] = $rowUserRegis["ACCOUNT_STATUS"];
		$getDataBank = $conmysql->prepare("SELECT gb.deptaccount_no_bank,gb.bank_account_name,cs.bank_short_name,gb.bind_date,gb.member_no
											FROM gcbindaccount gb LEFT JOIN csbankdisplay cs ON gb.bank_code = cs.bank_code
											WHERE member_no = :member_no and bindaccount_status = '1'");
		$getDataBank->execute([':member_no' => $rowUserRegis["MEMBER_NO"]]);
		while($rowDataBank = $getDataBank->fetch(PDO::FETCH_ASSOC)){
			$arrayBank = array();
			$arrayBank["DEPTACCOUNT_NO_BANK"] = $rowDataBank["deptaccount_no_bank"];
			$arrayBank["BANK_ACCOUNT_NAME"] = $rowDataBank["bank_account_name"];
			$arrayBank["BANK_NAME"] = $rowDataBank["bank_short_name"];
			$arrayBank["BIND_DATE"] = $lib->convertdate($rowDataBank["bind_date"],'D/n/Y');
			$arrayUserRegister["BANK_ACCOUNT"][] = $arrayBank;
		}
		$arrayGroup[] = $arrayUserRegister;
	}
	
	$arrayResult["USER_REGISTER"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
}else{
	http_response_code(500);
}
?>