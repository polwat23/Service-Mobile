<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_imagemap'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineactionimage')){
			$updateText = $conmysql->prepare("UPDATE lbimagemap SET is_use = '0'
													WHERE id_imagemap = :id_imagemap");
			if($updateText->execute([
				':update_by' => $payload["username"],
				':id_imagemap' => $dataComing["id_imagemap"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถลบรูปภาพได้ กรุณาติดต่อผู้พัฒนา";
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