<?php
ini_set('display_errors', false);
ini_set('error_log', __DIR__.'/../log/error.log');
error_reporting(E_ERROR);

header("Access-Control-Allow-Headers: Origin, Content-Type ,X-Requested-With, Accept, Authorization,Lang_locale,Request_token");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
function getAllowOrigin() {
    $httpOrigin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    $siteAllowOrigin = [
        "https://proxy.thaicoop.co",
        "https://mobilecore.gensoft.co.th",
		"https://uatmbkcoop.mbkgroup.co.th",
		"https://mbkcoop.mbkgroup.co.th"
    ];
    $siteAllowOriginWildcard = [
        "icoopsiam.com",
        "icoopsiam.local"
    ];
    $defaultAllowOrigin = "https://mbkcoop.mbkgroup.co.th";

    if (in_array($httpOrigin, $siteAllowOrigin)) {
        return $httpOrigin;
    } else if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', isset(parse_url($httpOrigin)['host']) ? parse_url($httpOrigin)['host'] : '', $regs)) {
        if (in_array($regs['domain'], $siteAllowOriginWildcard)) {
            return $httpOrigin;
        }
    }
    return $defaultAllowOrigin;
}
$allowOrigin = getAllowOrigin();
header("Access-Control-Allow-Origin: $allowOrigin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400");
header('Content-Type: application/json;charset=utf-8');
header('Cache-Control: max-age=86400');
header("X-Frame-Options: sameorigin");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src https: data: 'unsafe-inline' 'unsafe-eval'");

if ($forceNewSecurity == true && $isValidateRequestToken == false) {
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once(__DIR__.'/../include/exit_footer.php');
}


if (strtoupper($_SERVER['REQUEST_METHOD']) === 'GET') {
	http_response_code(500);
	exit;
}

foreach ($_SERVER as $header_key => $header_value){
	if($header_key == "HTTP_AUTHORIZATION"){
		$headers["Authorization"] = $header_value;
	}else if($header_key == "HTTP_LANG_LOCALE") {
		$headers["Lang_locale"] = $header_value;
	}else if ($header_key == "HTTP_REQUEST_TOKEN") {
		$headers["Request_token"] = $header_value;
	}
}
if( isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') ) {
   ob_start("ob_gzhandler");
}else{
   ob_start();
}

// Require files
require_once(__DIR__.'/../extension/vendor/autoload.php');
require_once(__DIR__.'/../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');
require_once(__DIR__.'/../include/control_log.php');
require_once(__DIR__.'/../include/cal_deposit.php');
require_once(__DIR__.'/../include/cal_loan.php');
require_once(__DIR__.'/../include/authorized.php');

// Call functions
use Utility\Library;
use Authorized\Authorization;
use Component\functions;
use ControlLog\insertLog;
use CalculateDeposit\CalculateDep;
use CalculateLoan\CalculateLoan;
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
$cal_dep = new CalculateDep();
$cal_loan = new CalculateLoan();
$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);
$jsonConfigError = file_get_contents(__DIR__.'/../config/config_indicates_error.json');
$configError = json_decode($jsonConfigError,true);
$jsonConfigAS = file_get_contents(__DIR__.'/../config/config_alias.json');
$configAS = json_decode($jsonConfigAS,true);
$lang_locale = $headers["Lang_locale"] ?? "th";

if(is_array($conmssql) && $conmssql["RESULT"] == FALSE){
	$message_error = $conmssql["MESSAGE"]." ".$conmssql["ERROR"];
	$lib->sendLineNotify($message_error);
	http_response_code(500);
	
}
if(is_array($conmssql) && $conmssql["RESULT"] == FALSE && $conmssql["IS_OPEN"] == '1'){
	$message_error = $conmssql["MESSAGE"]." ".$conmssql["ERROR"];
	$lib->sendLineNotify($message_error);
	$func->MaintenanceMenu("System");
	http_response_code(500);
	
}

if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
	$payload = array();
	// Complete Argument
	if(isset($headers["Authorization"]) && strlen($headers["Authorization"]) > 15){
		if(empty($headers["transaction_scheduler"])){
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
							require_once(__DIR__.'/../include/exit_footer.php');
							
						}
						if($dataComing["is_root"] == "1"){
							$insertBlackList = $conmssql->prepare("INSERT INTO gcdeviceblacklist(unique_id,member_no,type_blacklist)
																VALUES(:unique_id,:member_no,'1')");
							if($insertBlackList->execute([
								':unique_id' => $dataComing["unique_id"],
								':member_no' => $payload["member_no"]
							])){
								$arrayResult['RESPONSE_CODE'] = "WS0069";
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								http_response_code(401);
								require_once('../../include/exit_footer.php');
								
							}
						}
						$getMemberLogged = $conmssql->prepare("SELECT id_token FROM gcuserlogin 
															WHERE member_no = :member_no and channel = :channel and is_login = '1' and id_userlogin <> :id_userlogin");
						$getMemberLogged->execute([
							':member_no' => $payload["member_no"],
							':channel' => $adataComing["channel"],
							':id_userlogin' => $payload["id_userlogin"]
						]);
						while($rowIdToken = $getMemberLogged->fetch(PDO::FETCH_ASSOC)){
							$arrayIdToken[] = $rowIdToken["id_token"];
						}
						$updateLoggedOneDeviceGU = $conmssql->prepare("UPDATE gcuserlogin SET 
																	is_login = '-5',logout_date = GETDATE()
																	WHERE id_token IN(".implode(',',$arrayIdToken).")");
						$updateLoggedOneDeviceGU->execute();
						$updateLoggedOneDeviceGT = $conmssql->prepare("UPDATE gctoken SET rt_is_revoke = '-6',
																	at_is_revoke = '-6',rt_expire_date = GETDATE() ,at_expire_date = GETDATE()
																	WHERE id_token IN(".implode(',',$arrayIdToken).")");
						$updateLoggedOneDeviceGT->execute();
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
							require_once(__DIR__.'/../include/exit_footer.php');
							
						}
						$rowStatus = $func->checkAccStatus($payload["member_no"]);
						if(!$rowStatus){
							$func->revoke_alltoken($payload["id_token"],'-88');
							$arrayResult['RESPONSE_CODE'] = "WS0010";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0]['LOGOUT-88'][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							http_response_code(401);
							require_once(__DIR__.'/../include/exit_footer.php');
							
						}
						if($payload["exp"] <= time() + ($func->getConstant('limit_session_timeout') / 2)){
							$regen_token = $auth->refresh_accesstoken($dataComing["refresh_token"],
							$dataComing["unique_id"],$conmssql,$payload,$jwt_token,$config["SECRET_KEY_JWT"]);
							if(!$regen_token){
								$arrayResult['RESPONSE_CODE'] = "WS0014";
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								http_response_code(401);
								require_once('/../include/exit_footer.php');
							}
							$arrayResult['NEW_TOKEN'] = $regen_token["ACCESS_TOKEN"];
						}
					}catch (ValidateException $e) {
						$errorCode = $e->getCode();
						if($errorCode === 3){
							$arrayResult['RESPONSE_CODE'] = "WS0034";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							http_response_code(500);
							require_once(__DIR__.'/../include/exit_footer.php');
							
						}else if($errorCode === 4){
							if(isset($dataComing["channel"]) && $dataComing["channel"] == 'mobile_app'){
								$payload = $lib->fetch_payloadJWT($access_token,$jwt_token,$config["SECRET_KEY_JWT"]);
								if(!$skip_autoload){
									if($dataComing["menu_component"] != 'News' && $dataComing["menu_component"] != 'Pin' 
									&& $dataComing["menu_component"] != 'Landing' && $dataComing["menu_component"] != 'Event'  && $dataComing["menu_component"] != 'UpdateFCMToken' && $payload["user_type"] != '9'){
										$is_refreshToken_arr = $auth->CheckPeriodRefreshToken($dataComing["refresh_token"],$dataComing["unique_id"],$payload["id_token"],$conmssql);
										if($is_refreshToken_arr){
											$arrayResult['RESPONSE_CODE'] = "WS0046";
											$arrayResult['RESPONSE_MESSAGE'] = "";
											$arrayResult['RESULT'] = FALSE;
											http_response_code(401);
											require_once(__DIR__.'/../include/exit_footer.php');
											
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
												require_once(__DIR__.'/../include/exit_footer.php');
												
											}else{
												$arrayResult['RESPONSE_CODE'] = "WS0032";
												$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
												$arrayResult['RESULT'] = FALSE;
												http_response_code(401);
												require_once(__DIR__.'/../include/exit_footer.php');
												
											}
										}
									}
								}
							}else{
								$arrayResult['RESPONSE_CODE'] = "WS0053";
								$arrayResult['RESPONSE_MESSAGE'] = str_replace('${TIMEOUT}',intval($func->getConstant("limit_session_timeout"))/60,$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
								$arrayResult['RESULT'] = FALSE;
								http_response_code(401);
								require_once(__DIR__.'/../include/exit_footer.php');
								
							}
						}else{
							$arrayResult['RESPONSE_CODE'] = "WS0014";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							http_response_code(401);
							require_once(__DIR__.'/../include/exit_footer.php');
							
						}
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0031";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					http_response_code(400);
					require_once(__DIR__.'/../include/exit_footer.php');
					
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS4004";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				http_response_code(400);
				require_once(__DIR__.'/../include/exit_footer.php');
				
			}
		}else{
			$author_token = $headers["Authorization"];
			$access_token = substr($author_token,7);
			$jwt = new Jwt($access_token, $config["SECRET_KEY_JWT"]);
			$parse_token = new Parse($jwt, new Validate(), new Encode());
			$parsed_token = $parse_token->validate()
				->validateExpiration()
				->parse();
			$payload = $parsed_token->getPayload();
		}

	}else{
		$anonymous = true;
	}
}else{
	$arrayResult['RESULT'] = TRUE;
	http_response_code(203);
	require_once(__DIR__.'/../include/exit_footer.php');
	
}
?>