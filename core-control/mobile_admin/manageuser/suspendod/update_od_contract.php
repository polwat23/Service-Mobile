<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','contract_list'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','suspendod')){
		$conmysql->beginTransaction();
		foreach ($dataComing["contract_list"] as $contract) {
			if(isset($contract["SUSPENDOD_ID"]) && $contract["SUSPENDOD_ID"] != ""){
				$updateSuspend = $conmysql->prepare("update gcsuspendod SET is_use = :is_use, update_username = :username WHERE suspendod_id = :suspendod_id");
				if($updateSuspend->execute([
					':is_use' => $contract["IS_USE"] ? "1" : "0",
					':username' => $payload["username"],
					':suspendod_id' => $contract["SUSPENDOD_ID"]
				])){
				}else{
					$conmysql->rollback();
					$arrayResult["RESULT"] = FALSE;
					$arrayResult['RESPONSE'] = "แก้ไขสถานะไม่สำเร็จ";
					require_once('../../../../include/exit_footer.php');
				}
			}else{
				$insertSuspend = $conmysql->prepare("INSERT INTO gcsuspendod(member_no, loancontract_no, is_use, update_username) VALUES (:member_no, :loancontract_no, :is_use, :username)");
				if($insertSuspend->execute([
					':member_no' => $contract["MEMBER_NO"],
					':loancontract_no' => $contract["LOANCONTRACT_NO"],
					':is_use' => $contract["IS_USE"] ? "1" : "0",
					':username' => $payload["username"]
				])){
				}else{
					$conmysql->rollback();
					$arrayResult["RESULT"] = FALSE;
					$arrayResult['RESPONSE'] = "แก้ไขสถานะไม่สำเร็จ";
					require_once('../../../../include/exit_footer.php');
				}
			}
		}
		$arrayStruc = [
			':menu_name' => "suspendod",
			':username' => $payload["username"],
			':use_list' =>"update suspendod",
			':details' => "contract list : ".json_encode($dataComing["contract_list"])
		];
		$log->writeLog('manageuser',$arrayStruc);
		$conmysql->commit();
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
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