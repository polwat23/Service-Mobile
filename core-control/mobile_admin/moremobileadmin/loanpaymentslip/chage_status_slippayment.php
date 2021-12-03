<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','req_status','id_slip_paydept'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanpaymentslip')){
		$approveReqLoan = $conmysql->prepare("UPDATE gcslippaydept SET req_status = :req_status,remark = :remark WHERE id_slip_paydept = :id_slip_paydept");
		if($approveReqLoan->execute([
			':req_status' => $dataComing["req_status"] ?? null,
			':remark' => $dataComing["remark"] ?? null,
			':id_slip_paydept' => $dataComing["id_slip_paydept"]
		])){
			$arrayStruc = [
				':menu_name' => "loanpaymentslip",
				':username' => $payload["username"],
				':use_list' =>"update status",
				':details' => "user_update => ".$payload["username"].", reqloan_docs =>".$dataComing["id_slip_paydept"].", status =>".$dataComing["req_status"].", remark =>".$dataComing["remark"]
			];
			$log->writeLog('manageuser',$arrayStruc);
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ไม่สามารถเปลี่ยนสถานะรายการนี้ได้ กรุณาติดต่อผู้พัฒนา";
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