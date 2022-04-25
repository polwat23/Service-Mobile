<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','dept_id'],$dataComing)){
	if($dataComing["type"] == 'is_use'){
		$updatStatus = $conmysql->prepare("
										UPDATE
                                            webcoopdepartment
										SET
											is_use = :is_use,
											update_by = :update_by
										WHERE
											dept_id  = :dept_id ");						
		if($updatStatus->execute([
				':is_use' =>  '-9',
				':dept_id' => $dataComing["dept_id"],
				':update_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['data'] = $dataComing;
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		} 

	}else if($dataComing["type"] == 'rename'){
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcoopdepartment
										SET
											dept_name = :dept_name,
											update_by = :update_by
										WHERE
											dept_id  = :dept_id ");						
		if($updatStatus->execute([
				':dept_name' =>  $dataComing["dept_name"],
				':dept_id' => $dataComing["dept_id"],
				':update_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['data'] = [
				':dept_name' =>  $dataComing["dept_name"],
				':dept_id ' => $dataComing["dept_id "],
				':update_by' => $payload["username"]
			];
			echo json_encode($arrayResult);
			exit();
		} 
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
