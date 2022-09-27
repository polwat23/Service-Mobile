<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','title','address','latitude','longtitude'],$dataComing)){
	if($func->check_permission_core($payload,'line','linereslocationmsg')){
			$updateText = $conmysql->prepare("INSERT INTO lblocation(title, address, latitude, longtitude, update_by) 
															VALUES (:title, :address, :latitude, :longtitude, :update_by)");
			if($updateText->execute([
				':title' => $dataComing["title"],
				':address' => $dataComing["address"],
				':latitude' => $dataComing["latitude"],
				':longtitude' => $dataComing["longtitude"],
				':update_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มตำแหน่งได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['sd'] = [
					':title' => $dataComing["title"],
					':address' => $dataComing["address"],
					':latitude' => $dataComing["latitude"],
					':longtitude' => $dataComing["longtitude"],
					':update_by' => $payload["username"]
				];
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