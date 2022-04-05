<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_action'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineactions')){
		$updateText = $conmysql->prepare("UPDATE lbaction SET type=:type, text=:text, url=:url, area_x=:area_x, area_y=:area_y, width=:width, height=:height,
														label=:label, data=:data, mode=:mode, initial=:initial, max=:max, min=:min, update_by=:update_by 
														WHERE id_action=:id_action");
		if($updateText->execute([
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
			':update_by' => $payload["username"],
			':id_action' => $dataComing["id_action"]
		])){
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขข้อความได้ กรุณาติดต่อผู้พัฒนา";
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