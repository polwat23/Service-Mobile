<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_imagemap'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineactionimage')){
		$updateText = $conmysql->prepare("UPDATE lbimagemap SET image_url=:image_url, width_size=:width_size, height_size=:height_size
														WHERE id_imagemap=:id_imagemap");
		if($updateText->execute([
			':image_url' => $dataComing["image_url"],
			':width_size' => $dataComing["width_size"],
			':height_size' => $dataComing["height_size"],
			':id_imagemap' => $dataComing["id_imagemap"]
		])){
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขรูปภาพได้ กรุณาติดต่อผู้พัฒนา";
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