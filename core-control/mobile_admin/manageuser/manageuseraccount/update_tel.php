<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no','new_tel'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuseraccount',$conoracle)){
		$update_tel = $conoracle->prepare("UPDATE gcmemberaccount 
																SET PHONE_NUMBER = :new_tel
																WHERE  MEMBER_NO = :member_no");
		if($update_tel->execute([
			':new_tel' => $dataComing["new_tel"],
			':member_no' => $dataComing["member_no"] 
		])){
			$arrayStruc = [
				':menu_name' => "manageuser",
				':username' => $payload["username"],
				':use_list' => "change Tel",
				':details' => $dataComing["old_tel"].' , '.$dataComing["new_tel"]
			];
			
			$log->writeLog('manageuser',$arrayStruc,false,$conoracle);	
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่เสามารถทำรายการได้ กรุณาติดต่อผู้พัฒนา";
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















