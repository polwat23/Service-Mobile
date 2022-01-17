<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','file_id','filemapping_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantattachmentmapping')){
		$updateConst = $conmysql->prepare("UPDATE gcreqfileattachmentmapping SET file_id = :file_id, loangroup_code = :loangroup_code, max = :max, is_require = :is_require, update_user = :update_user 
									WHERE filemapping_id = :filemapping_id");
		if($updateConst->execute([
			':file_id' => $dataComing["file_id"],
			':loangroup_code' => $dataComing["loangroup_code"],
			':max' => $dataComing["max"],
			':is_require' => $dataComing["is_require"],
			':update_user' => $payload["username"],
			':filemapping_id' => $dataComing["filemapping_id"]
		])){
			$arrayStruc = [
				':menu_name' => "constantattachmentmapping",
				':username' => $payload["username"],
				':use_list' => "update constantattachmentmapping",
				':details' => $dataComing["filemapping_id"]." => file_id : ".$dataComing["file_id"]." loangroup_code : ".$dataComing["loangroup_code"]." max : ".$dataComing["max"]." is_require : ".$dataComing["is_require"]
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