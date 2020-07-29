<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','req_status','reqloan_doc'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestform')){
		if($dataComing["req_status"] == '1'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcreqloan SET req_status = '1',approve_date = NOW(),username = :username WHERE reqloan_doc = :reqloan_doc");
			if($approveReqLoan->execute([
				':username' => $payload["username"],
				':reqloan_doc' => $dataComing["reqloan_doc"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถอนุมัติใบคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
				echo json_encode($arrayResult);
				exit();
			}
		}else if($dataComing["req_status"] == '7'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcreqloan SET req_status = '7',username = :username WHERE reqloan_doc = :reqloan_doc");
			if($approveReqLoan->execute([
				':username' => $payload["username"],
				':reqloan_doc' => $dataComing["reqloan_doc"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถเปลี่ยนสถานะใบคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
				echo json_encode($arrayResult);
				exit();
			}
		}else if($dataComing["req_status"] == '-9'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcreqloan SET req_status = '-9',remark = :remark,username = :username WHERE reqloan_doc = :reqloan_doc");
			if($approveReqLoan->execute([
				':remark' => $dataComing["remark"] ?? null,
				':username' => $payload["username"],
				':reqloan_doc' => $dataComing["reqloan_doc"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถยกเลิกใบคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
				echo json_encode($arrayResult);
				exit();
			}
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