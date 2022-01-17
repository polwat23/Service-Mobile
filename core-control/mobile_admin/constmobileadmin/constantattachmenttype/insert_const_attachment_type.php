<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','file_name'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantattachmenttype')){
	
		$updateConst = $conmysql->prepare("INSERT INTO gcreqfileattachment(file_name, file_desc, update_user) 
									VALUES (:file_name, :file_desc, :update_user)");
		if($updateConst->execute([
			':file_name' => $dataComing["file_name"],
			':file_desc' => $dataComing["file_desc"],
			':update_user' => $payload["username"]
		])){
			$id_const = $conmysql->lastInsertId();
			$arrayStruc = [
				':menu_name' => "constantattachmenttype",
				':username' => $payload["username"],
				':use_list' => "insert constantattachmenttype",
				':details' => $id_const." => file_name : ".$dataComing["file_name"]." file_desc : ".$dataComing["file_desc"]." username : ".$payload["username"]
			];
			$log->writeLog('manageuser',$arrayStruc);
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มเอกสารได้ กรุณาติดต่อผู้พัฒนา";
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