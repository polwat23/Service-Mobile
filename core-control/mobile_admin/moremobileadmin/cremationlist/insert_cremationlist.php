<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','full_name','data_date'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','cremationlist')){
	
		$insertCremationList = $conmysql->prepare("INSERT INTO gccremationlist (full_name, data_date,cremation_amt , update_user, cremation_coop) VALUES (:full_name, :data_date ,:cremation_amt, :update_user, :cremation_coop)");
		if($insertCremationList->execute([
			':full_name' => $dataComing["full_name"],
			':data_date' => $dataComing["data_date"],
			':cremation_amt' => $dataComing["cremation_amt"],
			':update_user' => $payload["username"],
			':cremation_coop' => $dataComing["cremation_coop"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "เพิ่มรายการฌาปนกิจไม่สำเร็จ";
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