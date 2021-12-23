<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','file_name','file_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantattachmenttype')){
	
		$updateConst = $conmysql->prepare("UPDATE gcreqfileattachment SET file_name = :file_name, file_desc = :file_desc, update_user = :update_user 
									WHERE file_id = :file_id");
		if($updateConst->execute([
			':file_name' => $dataComing["file_name"],
			':file_desc' => $dataComing["file_desc"],
			':update_user' => $payload["username"],
			':file_id' => $dataComing["file_id"]
		])){
			$arrayStruc = [
				':menu_name' => "constantattachmenttype",
				':username' => $payload["username"],
				':use_list' => "update constantattachmenttype",
				':details' => $dataComing["file_id"]." => file_name : ".$dataComing["file_name"]." file_desc : ".$dataComing["file_desc"]." username : ".$payload["username"]
			];
			$log->writeLog('manageuser',$arrayStruc);
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเอกสารได้ กรุณาติดต่อผู้พัฒนา";
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