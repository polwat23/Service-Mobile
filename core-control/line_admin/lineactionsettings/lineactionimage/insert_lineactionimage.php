<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','image_url'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineactionimage')){
		$insertQuickText = $conmysql->prepare("INSERT INTO lbimagemap(image_url, width_size, height_size) 
														VALUES (:image_url, :width_size, :height_size)");
		if($insertQuickText->execute([
			':image_url' => $dataComing["image_url"],
			':width_size' => $dataComing["width_size"],
			':height_size' => $dataComing["height_size"],
		])){
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มรูปภาพได้ กรุณาติดต่อผู้พัฒนา";
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