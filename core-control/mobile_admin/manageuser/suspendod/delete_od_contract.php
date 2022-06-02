<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','suspendod_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','suspendod')){
		$arrayGroupAll = array();
		$updateSuspend = $conmysql->prepare("update gcsuspendod SET is_use = '0', update_username = :username WHERE suspendod_id = :suspendod_id");
		if($updateSuspend->execute([
			':username' => $payload["username"],
			':suspendod_id' => $dataComing["suspendod_id"]
		])){
			$arrayStruc = [
				':menu_name' => "suspendod",
				':username' => $payload["username"],
				':use_list' =>"delete suspendod",
				':details' => "suspendod_id : ".$dataComing["suspendod_id"]
			];
			$log->writeLog('manageuser',$arrayStruc);
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult["RESULT"] = FALSE;
			$arrayResult['RESPONSE'] = "แก้ไขสถานะไม่สำเร็จ";
			require_once('../../../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>