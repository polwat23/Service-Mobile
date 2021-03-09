<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_reqremark','remark_title','remark_detail'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantreqloanremark')){
		if(isset($dataComing["remark_title"]) && $dataComing["remark_title"] != '' && isset($dataComing["remark_detail"]) && $dataComing["remark_detail"] != ''){
			$updateConstants = $conmysql->prepare("UPDATE gcreqloanremark SET remark_title = :remark_title, remark_detail = :remark_detail, update_user = :update_user
													WHERE id_reqremark = :id_reqremark");
			if($updateConstants->execute([
				':remark_title' => $dataComing["remark_title"],
				':remark_detail' => $dataComing["remark_detail"],
				':update_user' => $payload["username"],
				':id_reqremark' => $dataComing["id_reqremark"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเป็นค่าว่างได้";
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