<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_post'],$dataComing)){
	if($dataComing["type"] == 'is_use'){
		$updatStatus = $conmysql->prepare("
										UPDATE
                                            webcooposition
										SET
											is_use = :is_use,
											update_by = :update_by
										WHERE
											id_post  = :id_post ");						
		if($updatStatus->execute([
				':is_use' =>  '-9',
				':id_post' => $dataComing["id_post"],
				':update_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		} 

	}else if($dataComing["type"] == 'rename'){
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcooposition
										SET
											post_name = :post_name,
											level = :level,
											update_by = :update_by
										WHERE
											id_post  = :id_post ");						
		if($updatStatus->execute([
				':post_name' =>  $dataComing["post_name"],
				':level' =>  $dataComing["level"],
				':id_post' => $dataComing["id_post"],
				':update_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		} 
	}else if($dataComing["type"] == 'level'){
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcooposition
										SET
											level = :level,
											update_by = :update_by
										WHERE
											id_post  = :id_post ");						
		if($updatStatus->execute([
				':level' =>  $dataComing["level"],
				':id_post' => $dataComing["id_post"],
				':update_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		} 
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารทำรายการได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['RESULT'] = FALSE;
	} 	
}else{
	$arrayResult['RESULT'] = FALSE;
	$arrayResult['data'] = $dataComing;

	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>
