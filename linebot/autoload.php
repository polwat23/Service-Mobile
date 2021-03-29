<?php
ini_set('display_errors', false);
ini_set('error_log', __DIR__.'/../log/error.log');
error_reporting(E_ERROR);


// Require files
require_once(__DIR__.'/../extension/vendor/autoload.php');
require_once(__DIR__.'./autoloadConnection.php');
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
use WebPConvert\WebPConvert;

$mailFunction = new PHPMailer(false);
$webP = new WebPConvert();
$lib = new library();
$auth = new Authorization();
$func = new functions();
$log = new insertLog();
$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);
$jsonConfigError = file_get_contents(__DIR__.'/../config/config_indicates_error.json');
$configError = json_decode($jsonConfigError,true);
$jsonConfigAS = file_get_contents(__DIR__.'/../config/config_alias.json');
$configAS = json_decode($jsonConfigAS,true);
$lang_locale = "th";

if(is_array($conmysql) && $conmysql["RESULT"] == FALSE){
	$message_error = $conmysql["MESSAGE"]." ".$conmysql["ERROR"];
	$lib->sendLineNotify($message_error);
}

if(is_array($conoracle) && $conoracle["RESULT"] == FALSE && $conoracle["IS_OPEN"] == '1'){
	$message_error = $conoracle["MESSAGE"]." ".$conoracle["ERROR"];
	$lib->sendLineNotify($message_error);
	$func->MaintenanceMenu("System");	
}
$dataComing = file_get_contents('php://input');
$dataComing = json_decode($dataComing, true);
$typeInput = $dataComing["events"][0]["type"];
$arrMessage = $dataComing["events"][0]["message"];
$user_id = $dataComing["events"][0]["source"]["userId"];
$reply_token = $dataComing["events"][0]["replyToken"];
$messageType = $arrMessage["type"];
$message = $arrMessage["text"];
require_once(__DIR__.'./mappingwordingline.php');
file_put_contents(__DIR__.'/../log/lineincome.txt', json_encode($dataComing["events"][0]) . PHP_EOL, FILE_APPEND);
?>