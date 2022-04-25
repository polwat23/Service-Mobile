<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_groupdownload'],$dataComing)){
	if($dataComing["type"] == 'is_use'){
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcoopgroupdownload
										SET
											is_use = :is_use,
											update_by = :update_by
										WHERE
											id_groupdownload = :id_groupdownload");						
		if($updatStatus->execute([
				':is_use' =>  '-9',
				':id_groupdownload' => $dataComing["id_groupdownload"],
				':update_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		} 

	}else if($dataComing["type"] == 'rename'){
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcoopgroupdownload
										SET
											name = :name,
											update_by = :update_by
										WHERE
											id_groupdownload = :id_groupdownload");						
		if($updatStatus->execute([
				':name' =>  $dataComing["name"],
				':id_groupdownload' => $dataComing["id_groupdownload"],
				':update_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		} 
	}else if($dataComing["type"] == 'title'){
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcoopgroupdownload
										SET
											parent = :parent,
											name = :name,
											update_by = :update_by
										WHERE
											id_groupdownload = :id_groupdownload");						
		if($updatStatus->execute([
				':parent' =>  $dataComing["parent"],
				':name' =>  $dataComing["name"],
				':id_groupdownload' => $dataComing["id_groupdownload"],
				':update_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['DAtsa'] = [
				':parent' =>  $dataComing["parent"],
				':name' =>  $dataComing["name"],
				':id_groupdownload' => $dataComing["id_groupdownload"],
				':update_by' => $payload["username"]
			];
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
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>