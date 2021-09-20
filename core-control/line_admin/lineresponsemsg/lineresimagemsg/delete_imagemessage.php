<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_imagemsg'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineresimagemsg')){
			$updateText = $conmysql->prepare("UPDATE lbimagemessage SET is_use = '0', update_by = :update_by
													WHERE id_imagemsg = :id_imagemsg");
			if($updateText->execute([
				':update_by' => $payload["username"],
				':id_imagemsg' => $dataComing["id_imagemsg"]
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