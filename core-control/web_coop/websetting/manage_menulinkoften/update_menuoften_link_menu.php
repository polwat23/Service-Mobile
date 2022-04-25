<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','webcoopoftenlink_id'],$dataComing)){
	if($dataComing["type"] == 'is_use'){
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcoopmenuoftenlink
										SET
											is_use = :is_use,
											update_by = :update_by
										WHERE
											webcoopoftenlink_id = :webcoopoftenlink_id");						
		if($updatStatus->execute([
				':is_use' =>  '-9',
				':webcoopoftenlink_id' => $dataComing["webcoopoftenlink_id"],
				':update_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถทำรายการได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		} 

	}else if($dataComing["type"] == 'update'){
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcoopmenuoftenlink
										SET
											id_menu = :id_menu,
											update_by = :update_by
										WHERE
											webcoopoftenlink_id = :webcoopoftenlink_id");						
		if($updatStatus->execute([
				':id_menu' =>  $dataComing["id_menu"],
				':webcoopoftenlink_id' => $dataComing["webcoopoftenlink_id"],
				':update_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถทำรายการได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		} 
	}else if($dataComing["update"] == 'menu_order'){
		$conmysql->beginTransaction();
		foreach($dataComing["menu_list"] as $menu_list){
			$updatemenu = $conmysql->prepare("UPDATE webcoopmenuoftenlink SET menu_order = :menu_order
										 WHERE webcoopoftenlink_id = :webcoopoftenlink_id");
			if($updatemenu->execute([
				':menu_order' => $menu_list["order"],
				':webcoopoftenlink_id' => $menu_list["webcoopoftenlink_id"]
			])){
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถจัดเรียงเมนูได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
		
		$conmysql->commit();

		$arrayResult["Data"] = [
				':menu_order' => $menu_list["order"],
				':id_menu' => $menu_list["id_menu"]
			];
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);	
 

	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารทำรายการได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['RESULT'] = FALSE;
	} 	
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>