<?php
header("Access-Control-Allow-Headers: Origin, Content-Type, database, X-Requested-With, Accept");
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

date_default_timezone_set("Asia/Bangkok");

require_once(__DIR__.'/../include/connection.php');
require_once(__DIR__.'/../include/validate_input.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');
require_once(__DIR__.'/../extension/PHPMailer-master/src/PHPMailer.php');
require_once(__DIR__.'/../extension/PHPMailer-master/src/SMTP.php');
require_once(__DIR__.'/../extension/PHPMailer-master/src/Exception.php');
require_once(__DIR__.'/../extension/jwt/autoload.php');

use Connection\connection;
use Utility\library;
use Component\functions;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use ReallySimpleJWT\Token;

$mailFunction = new PHPMailer(false);
$con = new connection();
$lib = new library();
$jwt_token = new Token();
$func = new functions();
$header = apache_request_headers();
$basetest = json_decode(isset($header["basetest"]) ? $header["basetest"] : false);
$conmysql = $con->connecttomysql($basetest);
$conoracle = $con->connecttooracle($basetest);
$jsonConfig = file_get_contents(__DIR__.'/../json/config_constructor.json');
$config = json_decode($jsonConfig,true);
?>