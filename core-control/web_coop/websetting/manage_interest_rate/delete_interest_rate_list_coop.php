<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','interestratelist_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','manageinterestrate')){
		$interestrate = $conmysql->prepare("DELETE FROM webcoopinterestratelist WHERE interestratelist_id = :interestratelist_id");						
		if($interestrate->execute([
				':interestratelist_id' =>  $dataComing["interestratelist_id"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบข้อมูลได้ กรุณาติดผู้ผัฒนา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}  
		
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