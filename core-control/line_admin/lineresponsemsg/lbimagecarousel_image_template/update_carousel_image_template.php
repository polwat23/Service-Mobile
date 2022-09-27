<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','update_data'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineresimagemsg')){
	
	$deleAction = $conmysql->prepare("UPDATE lbimagecarouseltemplate SET  update_by = :update_by WHERE id_imagecarousel = :id_imagecarousel");
	if($deleAction->execute([
		':id_imagecarousel' => $dataComing["id_imagecarousel"],
		':update_by' => $payload["username"]
	])){
		$data = array();
		foreach($dataComing["update_data"] AS $updateData){
			$delecolumns = $conmysql->prepare("UPDATE lbimagecarouselcolumns SET is_use ='-9',update_by = :update_by WHERE id_columns = :id_columns");
			if($delecolumns->execute([
				':update_by' => $payload["username"],
				':id_columns' => $updateData["ID_COLUMNS"]
			])){
				$arrayResult["message"][] = "del columns สำเร็จ";
				$action = $conmysql->prepare("UPDATE lbaction SET is_use ='-9',update_by = :update_by WHERE id_action = :id_action");
				if($action->execute([
					':update_by' => $payload["username"],
					':id_action' => $updateData["ACTION_ID"]
				])){
					$arrayResult["message"][] = "del action สำเร็จ";
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถทำรายการได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					$arrayResult['data'] = [
					':update_by' => $payload["username"],
					':id_action' => $updateData["ID_ACTION"]
				];
					
					require_once('../../../../include/exit_footer.php');	
				}
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถทำรายการได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');	
			}
			$data[] = $updateData;
		}
		$action = $conmysql->prepare("UPDATE lbimagecarouselmap SET is_use ='-9',update_by = :update_by WHERE imagecarousel_id = :imagecarousel_id");
		if($action->execute([
			':update_by' => $payload["username"],
			':imagecarousel_id' => $dataComing["id_imagecarousel"]
		])){
			$arrayResult["message"][] = "del imagecarouselmap สำเร็จ";
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถทำรายการได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['data'] = [
			':update_by' => $payload["username"],
			':id_action' => $updateData["ID_ACTION"]
		];
			
			require_once('../../../../include/exit_footer.php');	
		}
		
		foreach($dataComing["update_data"] AS $arrData){
			$id_action =  null;
			$i++;
			//inssert Action
			$conmysql->beginTransaction();
			$inserAction = $conmysql->prepare("INSERT INTO lbaction(type, text, url, area_x, area_y, width, height, label, data, mode, initial, max, min,update_by) 
														VALUES (:type, :text, :url, :area_x, :area_y, :width, :height, :label, :data, :mode, :initial, :max, :min, :update_by)");
			if($inserAction->execute([
				':type' => $arrData["TYPE"],
				':text' => $arrData["TEXT"],
				':url' => $arrData["URL"],
				':area_x' => $arrData["AREA_X"],
				':area_y' => $arrData["AREA_Y"],
				':width' => $arrData["WIDTH"],
				':height' => $arrData["HEIGHT"],
				':label' => $arrData["LABEL"],
				':data' => $arrData["DATA"],
				':mode' => $arrData["MODE"],
				':initial' => $arrData["INITIAL"],
				':max' => $arrData["MAX"],
				':min' => $arrData["MIN"],
				':update_by' => $payload["username"]
			])){
				$arrayResult["message"][] = "insert action สำเร็จ";
				$id_action = $conmysql->lastInsertId();
				$conmysql->commit();
		
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา1 ";
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['data'] = [
				':type' => $arrData["TYPE"],
				':text' => $arrData["TEXT"],
				':url' => $arrData["URL"],
				':area_x' => $arrData["AREA_X"],
				':area_y' => $arrData["AREA_Y"],
				':width' => $arrData["WIDTH"],
				':height' => $arrData["HEIGHT"],
				':label' => $arrData["LABEL"],
				':data' => $arrData["DATA"],
				':mode' => $arrData["MODE"],
				':initial' => $arrData["INITIAL"],
				':max' => $arrData["MAX"],
				':min' => $arrData["MIN"],
				':update_by' => $payload["username"]
			];
				
				require_once('../../../../include/exit_footer.php');
			}
			
			$conmysql->beginTransaction();
			$inserColumn = $conmysql->prepare("INSERT INTO lbimagecarouselcolumns(image_url,action_id,update_by) 
														VALUES (:image_url, :action_id, :update_by)");
			if($inserColumn->execute([
				':image_url' => $arrData["IMAGE_URL"],
				':action_id' => $id_action,
				':update_by' => $payload["username"]
				
			])){
				$columns_id[] = $conmysql->lastInsertId();
				$arrayResult["message"][] = "insert column สำเร็จ";
				$conmysql->commit();
				
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา2 ";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}
		
		foreach($columns_id AS $column){
			$conmysql->beginTransaction();
			$magecarouselmap = $conmysql->prepare("INSERT INTO lbimagecarouselmap(imagecarousel_id,columns_id,update_by) 
														VALUES (:imagecarousel_id, :columns_id, :update_by)");
			if($magecarouselmap->execute([
				':imagecarousel_id' => $dataComing["id_imagecarousel"],
				':columns_id' => $column,
				':update_by' => $payload["username"]
			])){
				$arrayResult["message"][] = "insert carouse map สำเร็จ";
				$conmysql->commit();
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา 3";
				$arrayResult['DATA'] = [
				':imagecarousel_id' => $imagecarousel_id,
				':columns_id' => $column,
				':update_by' => $payload["username"]
			];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}
		
		$arrayResult['data'] = $data;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถทำรายการได้ กรุณาติดต่อผู้พัฒนา";
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