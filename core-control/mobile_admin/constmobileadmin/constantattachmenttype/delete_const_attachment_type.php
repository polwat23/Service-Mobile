<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','file_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantattachmenttype')){
	
		$updateConst = $conmysql->prepare("UPDATE gcreqfileattachment SET is_use = '0', update_user = :update_user 
									WHERE file_id = :file_id");
		if($updateConst->execute([
			':update_user' => $payload["username"],
			':file_id' => $dataComing["file_id"]
		])){
			$arrayStruc = [
				':menu_name' => "constantattachmenttype",
				':username' => $payload["username"],
				':use_list' => "delete constantattachmenttype",
				':details' => $dataComing["file_id"]." username : ".$payload["username"]
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