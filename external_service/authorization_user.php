<?php
ini_set('display_errors', false);
ini_set('error_log', __DIR__.'/../log/error.log');
error_reporting(E_ERROR);

require_once(__DIR__.'/../include/connection.php');
require_once(__DIR__.'/../include/validate_input.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Connection\connection;
use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();
$con = new connection();
$conmysql = $con->connecttomysql();

header("Content-type: application/json;charset=utf8");

if($lib->checkCompleteArgument(['member_no','password'],$dataComing)){
	$member_no = strtoupper($lib->mb_str_pad($dataComing["member_no"]));
	$fetchMemberInfo = $conmysql->prepare("SELECT password,account_status FROM gcmemberaccount WHERE member_no = :member_no");
	$fetchMemberInfo->execute([':member_no' => $member_no]);
	$rowMember = $fetchMemberInfo->fetch(PDO::FETCH_ASSOC);
	$valid_pass = password_verify($dataComing["password"], $rowMember['password']);
	if($valid_pass){
		if($rowMember['account_status'] == '1'){
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
			exit();
		}else if($rowMember['account_status'] == '-8'){
			$arrayResult["RESPONSE_MESSAGE"] = "บัญชีนี้ถูกระงับ";
			$arrayResult["RESULT"] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}else if($rowMember['account_status'] == '-6'){
			$arrayResult["RESPONSE_MESSAGE"] = "สมาชิกท่านนี้ลาออกไปแล้ว";
			$arrayResult["RESULT"] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}else if($rowMember['account_status'] == '-7'){
			$arrayResult["RESPONSE_MESSAGE"] = "สมาชิกท่านนี้เกษียณอายุไปแล้ว";
			$arrayResult["RESULT"] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult["RESPONSE_MESSAGE"] = "รหัสผ่านไม่ถูกต้อง";
		$arrayResult["RESULT"] = FALSE;
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult["RESPONSE_MESSAGE"] = "ส่งค่ามาไม่ครบ";
	$arrayResult["RESULT"] = FALSE;
	echo json_encode($arrayResult);
	exit();
}
?>