<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_token','member_no','newpassword'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuseraccount')){
		$repassword = $conmysql->prepare("UPDATE gcmemberaccount SET password = :newpassword
									 WHERE member_no = :member_no")
		if($repassword->execute([
				':newpassword' => $newpassword,
				':member_no' => $member_no
			])){
				$arrayResult["RESULT"] = TRUE;
			}else{
				$arrayResult["RESULT"] = FALSE;
			}
		echo json_encode($arrayResult);	
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>