<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','deptaccount_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','deptaccountjoint')){
		$insertIntoInfo = $conmysql->prepare("UPDATE gcdeptaccountjoint SET is_joint='0' WHERE deptaccount_no = :deptaccount_no");
		if($insertIntoInfo->execute([
			':deptaccount_no' => $dataComing["deptaccount_no"],
		])){
			$arrayStruc = [
				':menu_name' => 'deptaccountjoint',
				':username' => $payload["username"],
				':use_list' => 'delete deptaccountjoint',
				':details' => "delete ".$dataComing["deptaccount_no"]
			];
			$log->writeLog('manageuser',$arrayStruc);	
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "ลบบัญชีร่วมไม่สำเร็จ กรุณาลองใหม่อีกครั้งในภายหลัง";
			$arrayResult['RESULT'] = FALSE;
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