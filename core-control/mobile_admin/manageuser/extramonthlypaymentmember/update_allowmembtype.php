<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_extrapayment','checked'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','extramonthlypaymentmember')){
		$arrayGroup = array();
		$updateMembType = $conmysql->prepare("UPDATE gcextrapaymentmembertype SET is_use = :checked WHERE id_extrapayment = :id_extrapayment");
		if($updateMembType->execute([
			':checked' => $dataComing["checked"],
			':id_extrapayment' => $dataComing["id_extrapayment"]
		])){
			$arrayStruc = [
				':menu_name' => "manageuser",
				':username' => $payload["username"],
				':use_list' => "edit extra payment membertype",
				':details' => $dataComing["id_extrapayment"].' => '.$dataComing["checked"]
			];
			
			$log->writeLog('manageuser',$arrayStruc);	
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขข้อมูลได้ กรุณาติดต่อผู้พัฒนา";
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