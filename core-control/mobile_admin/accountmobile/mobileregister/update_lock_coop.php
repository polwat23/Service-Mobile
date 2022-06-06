<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no','status_lock'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','mobileregister')){
		$update_phone = $conmysql->prepare("UPDATE gcmembonlineregis SET service_status = :status_lock WHERE  member_no = :member_no");
		if($update_phone->execute([
			':status_lock' => $dataComing["status_lock"],
			':member_no' => $dataComing["member_no"]			
		])){
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถล๊อกบัญชีสหกรณ์ได้";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
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