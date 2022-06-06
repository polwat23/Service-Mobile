<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no','new_position'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','mobileregister')){
		$update_phone = $conmysql->prepare("UPDATE gcmemberaccount SET position_desc= :new_position WHERE  member_no = :member_no");
		if($update_phone->execute([
			':new_position' => $dataComing["new_position"],
			':member_no' => $dataComing["member_no"]			
		])){
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเปลื่ยนตำเเหน่งได้ กรุณาติดต่อผู้พัฒนา";
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