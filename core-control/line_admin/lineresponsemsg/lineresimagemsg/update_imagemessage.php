<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_imagemsg','image_url'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineresimagemsg')){
		if(isset($dataComing["image_url"]) || $dataComing["image_url"] != ''){
			$updateText = $conmysql->prepare("UPDATE lbimagemessage SET image_url = :image_url, update_by = :update_by
													WHERE id_imagemsg = :id_imagemsg");
			if($updateText->execute([
				':image_url' => $dataComing["image_url"],
				':update_by' => $payload["username"],
				':id_imagemsg' => $dataComing["id_imagemsg"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขรูปภาพได้ กรุณาติดต่อผู้พัฒนา";
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