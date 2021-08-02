<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','image_url'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineresimagemsg')){
		if(isset($dataComing["image_url"]) || $dataComing["image_url"] != ''){
			$updateText = $conmysql->prepare("INSERT INTO lbimagemessage(image_url, update_by) 
															VALUES (:image_url, :update_by)");
			if($updateText->execute([
				':image_url' => $dataComing["image_url"],
				':update_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มรูปภาพได้ กรุณาติดต่อผู้พัฒนา";
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