<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no','account_status'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuseraccount')){
		$updateStatus = $conmysql->prepare("UPDATE gcmemberaccount SET account_status = :account_status
									 WHERE member_no = :member_no");
		if($updateStatus->execute([
			':account_status' => $dataComing["account_status"],
			':member_no' => $dataComing["member_no"]
		])){
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถล็อคหรือปลดล็อคบัญชีได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>