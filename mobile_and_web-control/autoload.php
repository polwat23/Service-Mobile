<?php
ini_set('display_errors', false);
ini_set('error_log', __DIR__.'/../log/error.log');

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
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');
require_once(__DIR__.'/../include/authorized.php');

// Call functions
use Utility\Library;
use Authorized\Authorization;
use Component\functions;
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
$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);
$lang_locale = $headers["Lang_locale"] ?? "th";

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
						if($lang_locale == 'th'){
							$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS4004";
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS4004";
						}
						$arrayResult['RESULT'] = FALSE;
						http_response_code(400);
						echo json_encode($arrayResult);
						exit();
					}
					if(!$func->checkLogin($payload["id_token"],$conmysql)){
						$arrayResult['RESPONSE_CODE'] = "WS0009";
						if($lang_locale == 'th'){
							$arrayResult['RESPONSE_MESSAGE'] = "กรุณาเข้าสู่ระบบ";
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = "Please login";
						}
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}catch (ValidateException $e) {
					$errorCode = $e->getCode();
					if($errorCode === 3){
						$arrayResult['RESPONSE_CODE'] = "WS0034";
						if($lang_locale == 'th'){
							$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถใช้งานได้ ลองเข้าสู่ระบบใหม่อีกครั้ง #WS0034";
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = "Cannot use please relogin #WS0034";
						}
						$arrayResult['RESULT'] = FALSE;
						http_response_code(401);
						echo json_encode($arrayResult);
						exit();
					}else if($errorCode === 4){
						$new_token = null;
						$is_refreshToken_arr = $auth->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,
						$dataComing["channel"],$lib->fetch_payloadJWT($access_token,$jwt_token,$config["SECRET_KEY_JWT"]),$jwt_token,$config["SECRET_KEY_JWT"]);
						if(!$is_refreshToken_arr){
							$arrayResult['RESPONSE_CODE'] = "WS0014";
							if($lang_locale == 'th'){
								$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถใช้งานได้ ลองเข้าสู่ระบบใหม่อีกครั้ง #WS0014";
							}else{
								$arrayResult['RESPONSE_MESSAGE'] = "Cannot use please relogin #WS0014";
							}
							$arrayResult['RESULT'] = FALSE;
							http_response_code(401);
							echo json_encode($arrayResult);
							exit();
						}else{
							$new_token = $is_refreshToken_arr["ACCESS_TOKEN"];
							$payload = $lib->fetch_payloadJWT($new_token,$jwt_token,$config["SECRET_KEY_JWT"]);
						}
					}else{
						$arrayResult['RESPONSE_CODE'] = "WS0032";
						if($lang_locale == 'th'){
							$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถใช้งานได้ ลองเข้าสู่ระบบใหม่อีกครั้ง #WS0032";
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = "Cannot use please relogin #WS0032";
						}
						$arrayResult['RESULT'] = FALSE;
						http_response_code(401);
						echo json_encode($arrayResult);
						exit();
					}
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0031";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถใช้งานได้ ลองเข้าสู่ระบบใหม่อีกครั้ง #WS0031";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "Cannot use please relogin #WS0031";
				}
				$arrayResult['RESULT'] = FALSE;
				http_response_code(400);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS4004";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS4004";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS4004";
			}
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