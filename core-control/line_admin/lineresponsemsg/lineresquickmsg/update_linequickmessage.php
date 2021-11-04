<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_quickmsg'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineresquickmsg')){
		$updateText = $conmysql->prepare("UPDATE lbquickmessage SET text = :text, update_by = :update_by
												WHERE id_quickmsg = :id_quickmsg");
		if($updateText->execute([
			':text' => $dataComing["text"],
			':update_by' => $payload["username"],
			':id_quickmsg' => $dataComing["id_quickmsg"]
		])){
			//delete msgmap old 
				$updateQmsMap = $conmysql->prepare("UPDATE lbquickmessagemap SET is_use ='-9',update_by = :update_by WHERE quickmessage_id = :id_quickmsg  ");
				if($updateQmsMap->execute([
					':id_quickmsg' => $dataComing["id_quickmsg"],
					':update_by' => $payload["username"],
				])){
					$arrayResult["message"][] = "ลบ msgmap สำเร็จ";
					
				}else{
					$arrayResult['RESPONSE'] = " ไม่สามารถทำรายการได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
			//update action old 
			foreach($dataComing["actionItem"] AS $arrAction){
				$deleAction = $conmysql->prepare("UPDATE lbaction SET is_use ='-9',update_by = :update_by WHERE id_action = :id_action");
				if($deleAction->execute([
					':update_by' => $payload["username"],
					':id_action' => $arrAction["ACTION_ID"]
				])){
					$arrayResult["message"][] = "ลบ Action สำเร็จ";
				
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถทำรายการได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');	
				}
			}
			$id_action = array();
			//insert action new
			foreach($dataComing["actionItem"] AS $arrActionData){
				//insert action
				$conmysql->beginTransaction();
				$insertQuickText = $conmysql->prepare("INSERT INTO lbaction(type, text, url, area_x, area_y, width, height, label, data, mode, initial, max, min,update_by) 
															VALUES (:type, :text, :url, :area_x, :area_y, :width, :height, :label, :data, :mode, :initial, :max, :min, :update_by)");
				if($insertQuickText->execute([
					':type' => $arrActionData["TYPE"],
					':text' => $arrActionData["TEXT"],
					':url' => $arrActionData["URL"],
					':area_x' => $arrActionData["AREA_X"],
					':area_y' => $arrActionData["AREA_Y"],
					':width' => $arrActionData["WIDTH"],
					':height' => $arrActionData["HEIGHT"],
					':label' => $arrActionData["LABEL"],
					':data' => $arrActionData["DATA"],
					':mode' => $arrActionData["MODE"],
					':initial' => $arrActionData["INITIAL"],
					':max' => $arrActionData["MAX"],
					':min' => $arrActionData["MIN"],
					':update_by' => $payload["username"]
				])){
					$id_action[] = $conmysql->lastInsertId();
					$arrayResult["message"][] = " เพิ่ม Action ใหม่  สำเร็จ";
					$conmysql->commit();
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อความได้ กรุณาติดต่อผู้พัฒนา  Case1";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
			}
			//insert Qmsmap
			foreach($id_action as $actionData){
				$insertBulkAction[] = "('".$dataComing["id_quickmsg"]."','".$actionData."','".$payload["username"]."')";
			}
			$insertMapping = $conmysql->prepare("INSERT INTO lbquickmessagemap(quickmessage_id, action_id,update_by) VALUES".implode(',',$insertBulkAction));
			if($insertMapping->execute()){
				$arrayResult["message"][] = " เพิ่ม  Qmsmap ใหม่  สำเร็จ";
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อความได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
			
				$arrayResult['RESULT'] = TRUE;
				$arrayResult['deleAction'] = $deleAction;
				require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขข้อความได้ กรุณาติดต่อผู้พัฒนา qms ";
			$arrayResult['data'] = [
			':update_by' => $payload["username"],
			':id_quickmsg' => $dataComing["id_quickmsg"]
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