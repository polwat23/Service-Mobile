<?php
header("Access-Control-Allow-Headers: Origin, Content-Type, X-Requested-With, Accept, Authorization ,basetest");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json;charset=utf-8');
header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
header('Pragma: no-cache');
header("X-Frame-Options: sameorigin");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src https: data: 'unsafe-inline' 'unsafe-eval'");

foreach ($_SERVER as $header_key => $header_value){
	if($header_key == "HTTP_AUTHORIZATION" ){
		$headers["Authorization"] = $header_value;
	}else if($header_key == "HTTP_BASETEST" ){
		$headers["basetest"] = $header_value;
	}
}

require_once(__DIR__.'/../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');
require_once(__DIR__.'/../authorized/authorized.php');
require_once(__DIR__.'/../extension/PHPMailer-master/src/PHPMailer.php');
require_once(__DIR__.'/../extension/PHPMailer-master/src/SMTP.php');
require_once(__DIR__.'/../extension/PHPMailer-master/src/Exception.php');
require_once(__DIR__.'/../extension/jwt/autoload.php');

use Utility\library;
use Authorized\API;
use Component\functions;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use ReallySimpleJWT\Token;

$mailFunction = new PHPMailer(false);
$lib = new library();
$api = new API();
$jwt_token = new Token();
$func = new functions();
$jsonConfig = file_get_contents(__DIR__.'/../json/config_constructor.json');
$config = json_decode($jsonConfig,true);
if(isset($headers["Authorization"]) && substr($headers["Authorization"],7) != null){
	$author_token = $headers["Authorization"];
	$access_token = substr($author_token,7);
	$payload = $lib->fetch_payloadJWT($access_token,$jwt_token,$config["SECRET_KEY_JWT"]);
}
?>