<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_reqremark'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantreqloanremark')){
			$updateConstants = $conmysql->prepare("UPDATE gcreqloanremark SET is_use = '0'
													WHERE id_reqremark = :id_reqremark");
			if($updateConstants->execute([
				':id_reqremark' => $dataComing["id_reqremark"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถลบค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
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