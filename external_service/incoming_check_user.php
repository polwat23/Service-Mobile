<?php
header("Access-Control-Allow-Headers: Origin, Content-Type ,X-Requested-With, Accept, Authorization ");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400");
header('Content-Type: application/json;charset=utf-8');
header('Cache-Control: max-age=86400');
header("X-Frame-Options: sameorigin");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src https: data: 'unsafe-inline' 'unsafe-eval'");

require_once(__DIR__.'/../include/validate_input.php');
require_once('../skipLoginFromCore.php');
require_once(__DIR__.'/../extension/vendor/autoload.php');
require_once(__DIR__.'/../include/lib_util.php');


use Utility\Library;
use ReallySimpleJWT\{Token,Parse,Jwt,Validate,Encode};

$lib = new library();
$jwt_token = new Token();

$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);

if(isset($dataComing["username"])){
	$device_name = $dataComing["device_name"];
	$username = $dataComing["username"];
	$getUserInfo = $conoracle->prepare("SELECT USER_NAME FROM amsecusers 
										WHERE USER_NAME = :username");
	$getUserInfo->execute([':username' => $username]);
	$rowUserInfo = $getUserInfo->fetch(PDO::FETCH_ASSOC);
	if(isset($rowUserInfo["USER_NAME"])){
		$arrPayload = array();
		$arrPayload['section_system'] = "root";
		$arrPayload['username'] = $rowUserInfo["USER_NAME"];
		$arrPayload['exp'] = time() + 21600;
		$access_token = $jwt_token->customPayload($arrPayload, $config["SECRET_KEY_CORE"]);
		if($rowUserInfo["USER_NAME"] != 'dev@mode'){
			$updateOldUser = $conoracle->prepare("UPDATE coreuserlogin SET is_login = '0' WHERE username = :username");
			$updateOldUser->execute([':username' => $rowUserInfo["USER_NAME"]]);
		}
		$queryMax = $conoracle->prepare("SELECT MAX(id_userlogin) as MAX_TABLE FROM coreuserlogin");
		$queryMax->execute();
		$rowQueryMax = $queryMax->fetch(PDO::FETCH_ASSOC);
		$id_userlogin = $rowQueryMax["MAX_TABLE"] + 1;
		$insertLog = $conoracle->prepare("INSERT INTO coreuserlogin(id_userlogin,username,unique_id,device_name,auth_token,logout_date ,login_date , update_date)
										VALUES(:id_userlogin,:username,:unique_id,:device_name,:token,
										TO_DATE(:logout_date,'yyyy/mm/dd hh24:mi:ss'), TO_DATE(:login_date,'yyyy/mm/dd hh24:mi:ss') ,TO_DATE(:update_date,'yyyy/mm/dd hh24:mi:ss'))");
		if($insertLog->execute([
		':id_userlogin' => $id_userlogin,
			':username' => $rowUserInfo["USER_NAME"],
			':unique_id' => $dataComing["unique_id"],
			':device_name' => $device_name,
			':token' => $access_token,
			':logout_date' => date('Y-m-d H:i:s', strtotime('+1 hour')),
			':login_date' => date('Y-m-d H:i:s'),
			':update_date' => date('Y-m-d H:i:s')
		])){
			$arrayResult["USERNAME"] = $rowUserInfo["USER_NAME"];
			$arrayResult["ACCESS_TOKEN"] = $access_token;
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = json_encode([
			':id_userlogin' => $id_userlogin,
			':username' => $dataComing["username"],
			':unique_id' => $dataComing["unique_id"],
			':device_name' => $dataComing["device_name"],
			':token' => $access_token,
			':logout_date' => date('Y-m-d H:i:s', strtotime('+1 hour')),
			':login_date' => date('Y-m-d H:i:s'),
			':update_date' => date('Y-m-d H:i:s')
		]);
			$arrayResult['RRR'] = json_encode($insertLog);
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถเข้าสู่ระบบได้ กรุณาลองใหม่อีกครั้ง";
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE'] = "ไม่สามารถเข้าสู่ระบบได้ กรุณาลองใหม่อีกครั้ง";
	$arrayResult['RESULT'] = FALSE;
	echo json_encode($arrayResult);
	exit();
}
?>