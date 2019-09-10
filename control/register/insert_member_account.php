<?php
require_once('../../autoload.php');

if(isset($dataComing["member_no"]) && isset($dataComing["password"])){
	$email = $dataComing["email"];
	$phone = $dataComing["phone"];
	$password = password_hash($dataComing["password"], PASSWORD_DEFAULT);
	$insertAccount = $conmysql->prepare("INSERT INTO mdbmemberaccount(member_no,password,phone_number,email,register_date,update_date) 
										VALUES(:member_no,:password,:phone,:email,NOW(),NOW())");
	if($insertAccount->execute([
		':member_no' => $dataComing["member_no"],
		':password' => $password,
		':phone' => $phone,
		':email' => $email
	])){
		$arrayResult = array();
		$arrayResult['MEMBER_NO'] = $dataComing["member_no"];
		$arrayResult['PASSWORD'] = $dataComing["password"];
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult = array();
		$arrayResult['RESPONSE_CODE'] = "SQL500";
		$arrayResult['RESPONSE'] = "Insert member account !!";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult = array();
	$arrayResult['RESPONSE_CODE'] = "PARAM400";
	$arrayResult['RESPONSE'] = "Not complete parameter";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>