<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_filedownload'],$dataComing)){
	if($dataComing["type"] == 'is_use'){
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcoopfiledownload
										SET
											is_use = :is_use,
											update_by = :update_by
										WHERE
											id_filedownload = :id_filedownload");						
		if($updatStatus->execute([
				':is_use' =>  '-9',
				':id_filedownload' => $dataComing["id_filedownload"],
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
											webcoopfiledownload
										SET
											file_name = :file_name,
											update_by = :update_by
										WHERE
											id_filedownload = :id_filedownload");						
		if($updatStatus->execute([
				':file_name' =>  $dataComing["file_name"],
				':id_filedownload' => $dataComing["id_filedownload"],
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