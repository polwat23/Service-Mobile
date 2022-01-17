<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','filemapping_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantattachmentmapping')){
	
		$updateConst = $conmysql->prepare("UPDATE gcreqfileattachmentmapping SET is_use = '0', update_user = :update_user 
									WHERE filemapping_id = :filemapping_id");
		if($updateConst->execute([
			':update_user' => $payload["username"],
			':filemapping_id' => $dataComing["filemapping_id"]
		])){
			$arrayStruc = [
				':menu_name' => "constantattachmentmapping",
				':username' => $payload["username"],
				':use_list' => "delete constantattachmentmapping",
				':details' => $dataComing["filemapping_id"]
			];
			$log->writeLog('manageuser',$arrayStruc);
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ไม่สามารถลบเอกสารได้ กรุณาติดต่อผู้พัฒนา";
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