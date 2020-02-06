<?php
ini_set('display_errors', false);
ini_set('error_log', __DIR__.'/../log/atm_external_error.log');
header("Access-Control-Allow-Methods: GET");

require_once(__DIR__.'/../extension/vendor/autoload.php');
require_once(__DIR__.'/../include/connection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/validate_input.php');

use Utility\library;
use Connection\connection;

$con = new connection();
$lib = new library();
$conmysql = $con->connecttomysql();

header("Access-Control-Allow-Origin: ".$origin);
header("Access-Control-Allow-Credentials: true");
header("X-Frame-Options: sameorigin");
header("X-XSS-Protection: 1; mode=block");
header('Content-Type: application/json;charset=utf-8');
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src https: data: 'unsafe-inline' 'unsafe-eval'");
		
$jsonConfig = file_get_contents(__DIR__.'/../json/config_external.json');
$config = json_decode($jsonConfig,true);
?>