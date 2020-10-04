<?php
ini_set('display_errors', false);
ini_set('error_log', __DIR__.'/../log/error.log');
error_reporting(E_ERROR);

header("Access-Control-Allow-Headers: Origin, Content-Type ,X-Requested-With, Accept, Authorization,Lang_locale");
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

if (strtoupper($_SERVER['REQUEST_METHOD']) === 'GET') {
	http_response_code(500);
	exit;
}

foreach ($_SERVER as $header_key => $header_value){
	if($header_key == "HTTP_AUTHORIZATION"){
		$headers["Authorization"] = $header_value;
	}else if($header_key == "HTTP_LANG_LOCALE") {
		$headers["Lang_locale"] = $header_value;
	}
}

// Require files
require_once(__DIR__.'/../extension/vendor/autoload.php');
require_once(__DIR__.'/../autoloadConnection.php');
require_once(__DIR__.'/../include/validate_input.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');
require_once(__DIR__.'/../include/control_log.php');
require_once(__DIR__.'/../include/authorized.php');

// Call functions
use Utility\Library;
use Authorized\Authorization;
use Component\functions;
use ControlLog\insertLog;
use PHPMailer\PHPMailer\{PHPMailer,Exception};
use ReallySimpleJWT\{Token,Parse,Jwt,Validate,Encode};
use ReallySimpleJWT\Exception\ValidateException;
use WebPConvert\WebPConvert;

$mailFunction = new PHPMailer(false);
$webP = new WebPConvert();
$lib = new library();
$auth = new Authorization();
$jwt_token = new Token();
$func = new functions();
$log = new insertLog();
$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);
$jsonConfigError = file_get_contents(__DIR__.'/../config/config_indicates_error.json');
$configError = json_decode($jsonConfigError,true);
$jsonConfigAS = file_get_contents(__DIR__.'/../config/config_alias.json');
$configAS = json_decode($jsonConfigAS,true);
$lang_locale = $headers["Lang_locale"] ?? "th";

if(is_array($conmysql) && $conmysql["RESULT"] == FALSE){
	$message_error = $conmysql["MESSAGE"]." ".$conmysql["ERROR"];
	$lib->sendLineNotify($message_error);
	http_response_code(500);
	exit();
}
if(is_array($conoracle) && $conoracle["RESULT"] == FALSE){
	$message_error = $conoracle["MESSAGE"]." ".$conoracle["ERROR"];
	$lib->sendLineNotify($message_error);
	//$func->MaintenanceMenu("System");
	http_response_code(500);
	exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
	$payload = array();
	// Complete Argument
	if(isset($headers["Authorization"]) && strlen($headers["Authorization"]) > 15){
		if($lib->checkCompleteArgument(['channel','refresh_token','unique_id'],$dataComing)){
			$author_token = $headers["Authorization"];
			if(substr($author_token,0,6) === 'Bearer'){
				$access_token = substr($author_token,7);
				$jwt = new Jwt($access_token, $config["SECRET_KEY_JWT"]);
				$parse_token = new Parse($jwt, new Validate(), new Encode());
				try{
					$parsed_token = $parse_token->validate()
						->validateExpiration()
						->parse();
					$payload = $parsed_token->getPayload();
					if(!$lib->checkCompleteArgument(['id_userlogin','member_no','exp','id_token','user_type'],$payload)){
						$arrayResult['RESPONSE_CODE'] = "WS4004";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						http_response_code(400);
						echo json_encode($arrayResult);
						exit();
					}
					$rowLogin = $func->checkLogin($payload["id_token"]);
					if(!$rowLogin["RETURN"]){
						if($rowLogin["IS_LOGIN"] == '-9' || $rowLogin["IS_LOGIN"] == '-10') {
							$func->revoke_alltoken($payload["id_token"],'-9',true);
						}else if($rowLogin["IS_LOGIN"] == '-8' || $rowLogin["IS_LOGIN"] == '-99'){
							$func->revoke_alltoken($payload["id_token"],'-8',true);
						}else if($rowLogin["IS_LOGIN"] == '-7'){
							$func->revoke_alltoken($payload["id_token"],'-7',true);
						}else if($rowLogin["IS_LOGIN"] == '-5'){
							$func->revoke_alltoken($payload["id_token"],'-6',true);
						}
						$arrayResult['RESPONSE_CODE'] = "WS0010";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0]['LOGOUT'.$rowLogin["IS_LOGIN"]][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						http_response_code(401);
						echo json_encode($arrayResult);
						exit();
					}
					$rowStatus = $func->checkAccStatus($payload["member_no"]);
					if(!$rowStatus){
						$func->revoke_alltoken($payload["id_token"],'-88');
						$arrayResult['RESPONSE_CODE'] = "WS0010";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0]['LOGOUT-88'][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						http_response_code(401);
						echo json_encode($arrayResult);
						exit();
					}
				}catch (ValidateException $e) {
					$errorCode = $e->getCode();
					if($errorCode === 3){
						$arrayResult['RESPONSE_CODE'] = "WS0034";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						http_response_code(401);
						echo json_encode($arrayResult);
						exit();
					}else if($errorCode === 4){
						if(isset($dataComing["channel"]) && $dataComing["channel"] == 'mobile_app'){
							$payload = $lib->fetch_payloadJWT($access_token,$jwt_token,$config["SECRET_KEY_JWT"]);
							if(!$skip_autoload){
								if($dataComing["menu_component"] != 'News' && $dataComing["menu_component"] != 'Pin' 
								&& $dataComing["menu_component"] != 'Landing' && $dataComing["menu_component"] != 'Event'  && $dataComing["menu_component"] != 'UpdateFCMToken' && $payload["user_type"] != '9'){
									$is_refreshToken_arr = $auth->CheckPeriodRefreshToken($dataComing["refresh_token"],$dataComing["unique_id"],$payload["id_token"],$conmysql);
									if($is_refreshToken_arr){
										$arrayResult['RESPONSE_CODE'] = "WS0046";
										$arrayResult['RESPONSE_MESSAGE'] = "";
										$arrayResult['RESULT'] = FALSE;
										http_response_code(401);
										echo json_encode($arrayResult);
										exit();
									}else{
										$rowLogin = $func->checkLogin($payload["id_token"]);
										if(!$rowLogin["RETURN"]){
											if($rowLogin["IS_LOGIN"] == '-9' || $rowLogin["IS_LOGIN"] == '-10') {
												$func->revoke_alltoken($payload["id_token"],'-9',true);
											}else if($rowLogin["IS_LOGIN"] == '-8' || $rowLogin["IS_LOGIN"] == '-99'){
												$func->revoke_alltoken($payload["id_token"],'-8',true);
											}else if($rowLogin["IS_LOGIN"] == '-7'){
												$func->revoke_alltoken($payload["id_token"],'-7',true);
											}else if($rowLogin["IS_LOGIN"] == '-5'){
												$func->revoke_alltoken($payload["id_token"],'-6',true);
											}
											$arrayResult['RESPONSE_CODE'] = "WS0010";
											$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0]['LOGOUT'.$rowLogin["IS_LOGIN"]][0][$lang_locale];
											$arrayResult['RESULT'] = FALSE;
											http_response_code(401);
											echo json_encode($arrayResult);
											exit();
										}else{
											$arrayResult['RESPONSE_CODE'] = "WS0032";
											$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
											$arrayResult['RESULT'] = FALSE;
											http_response_code(401);
											echo json_encode($arrayResult);
											exit();
										}
									}
								}
							}
						}else{
							$arrayResult['RESPONSE_CODE'] = "WS0053";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							http_response_code(401);
							echo json_encode($arrayResult);
							exit();
						}
					}else{
						$arrayResult['RESPONSE_CODE'] = "WS0014";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						http_response_code(401);
						echo json_encode($arrayResult);
						exit();
					}
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0031";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				http_response_code(400);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS4004";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(400);
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$anonymous = true;
	}
}else{
	$arrayResult['RESULT'] = TRUE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>