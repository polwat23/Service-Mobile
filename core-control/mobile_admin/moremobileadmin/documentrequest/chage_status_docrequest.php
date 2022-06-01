<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','req_status','reqdoc_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','documentrequest')){
		$conmysql->beginTransaction();
		$approveReqLoan = $conmysql->prepare("UPDATE gcreqdoconline SET req_status = :req_status,remark = :remark WHERE reqdoc_no = :reqdoc_no");
		if($approveReqLoan->execute([
			':req_status' => $dataComing["req_status"] ?? null,
			':remark' => $dataComing["remark"] ?? null,
			':reqdoc_no' => $dataComing["reqdoc_no"]
		])){
			//Start Update slip
			$approveReqLoan = $conmysql->prepare("UPDATE gcslippaydept SET req_status = :req_status,remark = :remark WHERE reqdoc_no = :reqdoc_no");
			if($approveReqLoan->execute([
				':req_status' => $dataComing["req_status"] ?? null,
				':remark' => $dataComing["remark"] ?? null,
				':reqdoc_no' => $dataComing["reqdoc_no"]
			])){
			}else{
				$conmysql->rollback();
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถเปลี่ยนสถานะรายการนี้ได้ กรุณาติดต่อผู้พัฒนา";
				echo json_encode($arrayResult);
				exit();
			}
			//End Update slip
			$conmysql->commit();
			$arrayStruc = [
				':menu_name' => "documentrequest",
				':username' => $payload["username"],
				':use_list' =>"update status",
				':details' => "user_update => ".$payload["username"].", reqloan_docs =>".$dataComing["reqloan_doc"].", status =>".$dataComing["req_status"].", remark =>".$dataComing["remark"]
			];
			$log->writeLog('manageuser',$arrayStruc);
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$conmysql->rollback();
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ไม่สามารถเปลี่ยนสถานะใบคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
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