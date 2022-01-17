<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','file_id','loangroup_code'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantattachmentmapping')){
	
		$updateConst = $conmysql->prepare("INSERT INTO gcreqfileattachmentmapping(file_id, loangroup_code, max, is_require, update_user) 
									VALUES (:file_id, :loangroup_code, :max, :is_require, :update_user) ");
		if($updateConst->execute([
			':file_id' => $dataComing["file_id"],
			':loangroup_code' => $dataComing["loangroup_code"],
			':max' => $dataComing["max"],
			':is_require' => $dataComing["is_require"],
			':update_user' => $payload["username"]
		])){
			$id_const = $conmysql->lastInsertId();
			$arrayStruc = [
				':menu_name' => "constantattachmentmapping",
				':username' => $payload["username"],
				':use_list' => "insert constantattachmentmapping",
				':details' => $id_const." => file_id : ".$dataComing["file_id"]." loangroup_code : ".$dataComing["loangroup_code"]." max : ".$dataComing["max"]." is_require : ".$dataComing["is_require"]
			];
			$log->writeLog('manageuser',$arrayStruc);
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['updateConst'] = $updateConst;
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