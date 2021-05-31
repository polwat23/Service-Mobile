<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantloanpaydate')){
		$arrayGroup = array();
		$fetchConstLoanPayDate = $conmysql->prepare("INSERT INTO gcconstantloanpaydate(loanpaydate, create_by, update_by) 
												VALUES (:loanpaydate,:create_by,:update_by)");
		if($fetchConstLoanPayDate->execute([
			':loanpaydate' => $dataComing["loanpaydate"],
			':create_by' => $payload["username"],
			':update_by' => $payload["username"],
		])){
		}else{
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
			echo json_encode($arrayResult);
			exit();
		}
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
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