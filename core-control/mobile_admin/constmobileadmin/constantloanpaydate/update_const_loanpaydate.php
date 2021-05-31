<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_loanpaydate'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantloanpaydate')){
		$updateConst = $conmysql->prepare("UPDATE gcconstantloanpaydate SET is_use = '0',update_by = :update_by WHERE id_loanpaydate = :id_loanpaydate");
		if($updateConst->execute([
		':update_by' => $payload["username"],
			':id_loanpaydate' => $dataComing["id_loanpaydate"]
		])){
			$arrayStruc = [
				':menu_name' => "constantloanpaydate",
				':username' => $payload["username"],
				':use_list' =>"update gcconstantloanpaydate",
				':details' => $dataComing["id_loanpaydate"]
			];
			$log->writeLog('manageuser',$arrayStruc);
		}else{
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ไม่สามารถลบวันที่ได้ กรุณาติดต่อผู้พัฒนา";
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