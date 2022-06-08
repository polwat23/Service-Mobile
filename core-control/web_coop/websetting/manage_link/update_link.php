<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','webcooplink_id'],$dataComing)){
	if($dataComing["type"] == 'is_use'){
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcooplink
										SET
											is_use = :is_use,
											update_by = :update_by
										WHERE
											webcooplink_id = :webcooplink_id");						
		if($updatStatus->execute([
				':is_use' =>  '-9',
				':webcooplink_id' => $dataComing["webcooplink_id"],
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
											webcooplink
										SET
											name = :name,
											link = :link,
											update_by = :update_by
										WHERE
											webcooplink_id = :webcooplink_id");						
		if($updatStatus->execute([
				':name' =>  $dataComing["name"],
				':link' =>  $dataComing["link"],
				':webcooplink_id' => $dataComing["webcooplink_id"],
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
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>