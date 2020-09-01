<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','manageactivitywebcoop')){
			
			$deleteGallery = $conmysql->prepare("UPDATE webcoopactivity SET 
														activity_title = :activity_title
													WHERE webcoopactivity_id = :activity_id");
			if($deleteGallery->execute([
				':activity_title' =>  $dataComing["activity_title"],
				':activity_id' =>  $dataComing["activity_id"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามาลบข่าวสารได้ กรุณาติดต่อผู้พัฒนา  ";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>