<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','update'],$dataComing)){
	if($dataComing["update"] == 'menu_status'){
		$updatStatus = $conmysql->prepare("
										UPDATE
                                                webcoopmenu
										SET
											menu_status = :menu_status,
											update_by = :update_by
										WHERE
											id_menu = :id_menu");						
		if($updatStatus->execute([
				':menu_status' => $dataComing["menu_status"],
				':id_menu' => $dataComing["id_menu"],
				':update_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารทำรายการได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult["Data"] = [
				':menu_status' => $dataComing["menu_status"],
				':id_menu' => $dataComing["id_menu"],
				':update_by' => $payload["username"]
			];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		} 

	 }else if($dataComing["update"] == 'rename'){
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcoopmenu
										SET
											menu_name = :menu_name,
											update_by = :update_by
										WHERE
											id_menu = :id_menu");						
		if($updatStatus->execute([
				':menu_name' =>  $dataComing["menu_name"],
				':id_menu' => $dataComing["id_menu"],
				':update_by' => $payload["username"]
			])){
		
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
		}else{
					$arrayResult["data"] =[
				':menu_name' =>  $dataComing["menu_name"],
				':id_menu' => $dataComing["id_menu"],
				':update_by' => $payload["username"]
			];
			$arrayResult['RESPONSE'] = "ไม่สามารทำรายการได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}

	}else if($dataComing["update"] == 'menu_order'){
		$conmysql->beginTransaction();
		foreach($dataComing["menu_list"] as $menu_list){
			$updatemenu = $conmysql->prepare("UPDATE webcoopmenu SET menu_order = :menu_order
										 WHERE id_menu = :id_menu");
			if($updatemenu->execute([
				':menu_order' => $menu_list["order"],
				':id_menu' => $menu_list["menu_id"]
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