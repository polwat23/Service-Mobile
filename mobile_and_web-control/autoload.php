<?php
header("Access-Control-Allow-Headers: Origin, Content-Type, basetest, X-Requested-With, Accept, Authorization");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json;charset=utf-8');
header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
header('Pragma: no-cache');
header("X-Frame-Options: sameorigin");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src https: data: 'unsafe-inline' 'unsafe-eval'");

require_once(__DIR__.'/../autoloadConnection.php');
require_once(__DIR__.'/../include/validate_input.php');
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
if(isset($header["Authorization"]) && substr($header["Authorization"],7) != null){
	$author_token = $header["Authorization"];
	$payload = $lib->fetch_payloadJWT($author_token,$jwt_token,$config["SECRET_KEY_JWT"]);
	$access_token = substr($author_token,7);
}
?>