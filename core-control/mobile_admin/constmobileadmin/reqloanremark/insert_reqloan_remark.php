<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','remark_title','remark_detail'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantreqloanremark')){
		if(isset($dataComing["remark_title"]) && $dataComing["remark_title"] != '' && isset($dataComing["remark_detail"]) && $dataComing["remark_detail"] != ''){
			$updateConstants = $conmysql->prepare("INSERT INTO gcreqloanremark (remark_title, remark_detail, create_user)
												VALUES (:remark_title, :remark_detail, :create_user)");
			if($updateConstants->execute([
				':remark_title' => $dataComing["remark_title"],
				':remark_detail' => $dataComing["remark_detail"],
				':create_user' => $payload["create_user"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มเป็นค่าว่างได้";
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