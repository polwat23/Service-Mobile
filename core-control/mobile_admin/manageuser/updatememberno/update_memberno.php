<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no','new_member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','editmemberno')){
		$conmysql->beginTransaction();
		$arrayExecute = array();
		if(isset($dataComing["new_member_no"]) && $dataComing["new_member_no"] != ''){
			$arrayExecute[':new_member_no'] = strtolower($lib->mb_str_pad($dataComing["new_member_no"]));
		}
		if(isset($dataComing["member_no"]) && $dataComing["member_no"] != ''){
			$arrayExecute[':member_no'] = strtolower($lib->mb_str_pad($dataComing["member_no"]));
		}
		
		// 1 update gcmemberaccount
		$update_gcmemberaccount = $conmysql->prepare("UPDATE gcmemberaccount 
											SET member_no = TRIM(:new_member_no)
											WHERE  member_no = TRIM(:member_no)");
		if($update_gcmemberaccount->execute($arrayExecute)){
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเลขสมาชิกได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
		// 2 update gcuserlogin
		$update_gcuserlogin = $conmysql->prepare("UPDATE gcuserlogin 
											SET member_no = TRIM(:new_member_no)
											WHERE  member_no = TRIM(:member_no)");
		if($update_gcuserlogin->execute($arrayExecute)){
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเลขสมาชิกได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
		// 3 update gcdeviceblacklist
		$update_gcdeviceblacklist = $conmysql->prepare("UPDATE gcdeviceblacklist 
											SET member_no = TRIM(:new_member_no)
											WHERE  member_no = TRIM(:member_no)");
		if($update_gcdeviceblacklist->execute($arrayExecute)){
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเลขสมาชิกได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
		// 4 update gchistory
		$update_gchistory = $conmysql->prepare("UPDATE gchistory 
											SET member_no = TRIM(:new_member_no)
											WHERE  member_no = TRIM(:member_no)");
		if($update_gchistory->execute($arrayExecute)){
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเลขสมาชิกได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
		
		//log
		$arrayStruc = [
			':menu_name' => "updatememberno",
			':username' => $payload["username"],
			':use_list' => "update memberno",
			':details' => $dataComing["member_no"] ?? "-".' , '.$dataComing["new_member_no"]
		];
		$log->writeLog('manageuser',$arrayStruc);	
		
		$arrayResult["RESULT"] = TRUE;
		$conmysql->commit();
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