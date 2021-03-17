<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no','new_email'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','mobileregister')){
		$update_email = $conmysql->prepare("UPDATE gcmemberaccount SET email = :new_email WHERE member_no = :member_no");
		if($update_email->execute([
			':new_email' => $dataComing["new_email"],
			':member_no' => $dataComing["member_no"]			
		])){
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเปลื่ยนอีเมลได้ กรุณาติดต่อผู้พัฒนา";
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
