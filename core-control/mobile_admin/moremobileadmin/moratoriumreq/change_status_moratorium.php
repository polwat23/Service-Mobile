<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','req_status','id_mora'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','moratoriumreq')){
		if($dataComing["req_status"] == '1'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcmoratorium SET is_moratorium = '1' WHERE id_mora = :id_mora");
			if($approveReqLoan->execute([
				':id_mora' => $dataComing["id_mora"]
			])){
				$arrayStruc = [
					':menu_name' => 'moratoriumreq',
					':username' => $payload["username"],
					':use_list' => 'change status moratorium',
					':details' => $dataComing["member_no"]." => status : 1"
				];
				
				$log->writeLog('manageuser',$arrayStruc);	
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถอนุมัติคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
				echo json_encode($arrayResult);
				exit();
			}
		}else if($dataComing["req_status"] == '0'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcmoratorium SET is_moratorium = '0' WHERE id_mora = :id_mora");
			if($approveReqLoan->execute([
				':id_mora' => $dataComing["id_mora"]
			])){
				$arrayStruc = [
					':menu_name' => 'moratoriumreq',
					':username' => $payload["username"],
					':use_list' => 'change status moratorium',
					':details' => $dataComing["member_no"]." => status : 0"
				];
				
				$log->writeLog('manageuser',$arrayStruc);
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถยกเลิกคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
				echo json_encode($arrayResult);
				exit();
			}
		}else if($dataComing["req_status"] == '-9'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcmoratorium SET is_moratorium = '0', cancel_date = now() WHERE id_mora = :id_mora");
			if($approveReqLoan->execute([
				':id_mora' => $dataComing["id_mora"]
			])){
				$arrayStruc = [
					':menu_name' => 'moratoriumreq',
					':username' => $payload["username"],
					':use_list' => 'change status moratorium',
					':details' => $dataComing["member_no"]." => status : 0 , cancel_date = ".date("Y-m-d H:i:s")
				];
				
				$log->writeLog('manageuser',$arrayStruc);
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถยกเลิกคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
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