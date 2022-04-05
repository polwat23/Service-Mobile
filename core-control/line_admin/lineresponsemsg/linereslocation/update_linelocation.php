<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','title','address','latitude','longtitude','id_location'],$dataComing)){
	if($func->check_permission_core($payload,'line','linereslocationmsg')){
			$updateText = $conmysql->prepare("UPDATE lblocation SET title = :title, address = :address, latitude = :latitude, longtitude = :longtitude, update_by = :update_by
													WHERE id_location = :id_location");
			if($updateText->execute([
				':title' => $dataComing["title"],
				':address' => $dataComing["address"],
				':latitude' => $dataComing["latitude"],
				':longtitude' => $dataComing["longtitude"],
				':update_by' => $payload["username"],
				':id_location' => $dataComing["id_location"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขตำแหน่งได้ กรุณาติดต่อผู้พัฒนา";
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