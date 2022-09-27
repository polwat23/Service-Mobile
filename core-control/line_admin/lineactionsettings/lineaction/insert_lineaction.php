<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineactions')){
		$insertQuickText = $conmysql->prepare("INSERT INTO lbaction(type, text, url, area_x, area_y, width, height, label, data, mode, initial, max, min,update_by) 
														VALUES (:type, :text, :url, :area_x, :area_y, :width, :height, :label, :data, :mode, :initial, :max, :min, :update_by)");
		if($insertQuickText->execute([
			':type' => $dataComing["type"],
			':text' => $dataComing["text"],
			':url' => $dataComing["url"],
			':area_x' => $dataComing["area_x"],
			':area_y' => $dataComing["area_y"],
			':width' => $dataComing["width"],
			':height' => $dataComing["height"],
			':label' => $dataComing["label"],
			':data' => $dataComing["data"],
			':mode' => $dataComing["mode"],
			':initial' => $dataComing["initial"],
			':max' => $dataComing["max"],
			':min' => $dataComing["min"],
			':update_by' => $payload["username"]
		])){
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อความได้ กรุณาติดต่อผู้พัฒนา";
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