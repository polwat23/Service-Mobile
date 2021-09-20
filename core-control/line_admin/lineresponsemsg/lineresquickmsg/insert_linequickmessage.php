<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','text'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineresquickmsg')){
		//if(isset($dataComing["id_action"]) || $dataComing["id_action"] != ''){
			//insert qickmessage
			$conmysql->beginTransaction();
			$updateText = $conmysql->prepare("INSERT INTO lbquickmessage (text,update_by)
													VALUES (:text,:update_by)");
			if($updateText->execute([
				':text' => $dataComing["text"],
				':update_by' => $payload["username"]
			])){
				$id_qickmsg = $conmysql->lastInsertId();
				$conmysql->commit();
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อความได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
			
			if($dataComing["insert_type"] == "multi"){
				foreach($dataComing["actionItem"] AS $arrAction){
					//insert action
				
					$conmysql->beginTransaction();
					$insertQuickText = $conmysql->prepare("INSERT INTO lbaction(type, text, url, area_x, area_y, width, height, label, data, mode, initial, max, min,update_by) 
																VALUES (:type, :text, :url, :area_x, :area_y, :width, :height, :label, :data, :mode, :initial, :max, :min, :update_by)");
					if($insertQuickText->execute([
						':type' => $dataComing["type"],
						':text' => $arrAction["TEXT"],
						':url' => $arrAction["URL"],
						':area_x' => $arrAction["AREA_X"],
						':area_y' => $arrAction["AREA_Y"],
						':width' => $arrAction["WIDTH"],
						':height' => $arrAction["HEIGHT"],
						':label' => $arrAction["LABEL"],
						':data' => $arrAction["DATA"],
						':mode' => $arrAction["MODE"],
						':initial' => $arrAction["INITIAL"],
						':max' => $arrAction["MAX"],
						':min' => $arrAction["MIN"],
						':update_by' => $payload["username"]
					])){
						$id_action[] = $conmysql->lastInsertId();
						$conmysql->commit();
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อความได้ กรุณาติดต่อผู้พัฒนา  Case1";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
					
				}
			}else{
				
				$conmysql->beginTransaction();
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
					$id_action[] = $conmysql->lastInsertId();
					$conmysql->commit();
					
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อความได้ กรุณาติดต่อผู้พัฒนา Case2";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
		
		}
		
		foreach($id_action as $actionData){
			$insertBulkAction[] = "('".$id_qickmsg."','".$actionData."','".$$payload["username"]."')";
		}
		
		$insertMapping = $conmysql->prepare("INSERT INTO lbquickmessagemap(quickmessage_id, action_id,update_by) VALUES".implode(',',$insertBulkAction));
		
		if($insertMapping->execute()){
	
			$arrayResult["RESULT"] = TRUE;
			$arrayResult['data'] = $insertBulkAction;
			require_once('../../../../include/exit_footer.php');
		}else{
			
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อความได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['data'] = $insertBulkAction;
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