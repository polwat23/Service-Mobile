<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','interestrate_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','manageinterestrate')){
		$interestrate = $conmysql->prepare("DELETE FROM webcoopinterestrate WHERE interestrate_id = :interestrate_id");						
		if($interestrate->execute([
				':interestrate_id' =>  $dataComing["interestrate_id"]
			])){
				
					$interestratelist = $conmysql->prepare("DELETE FROM webcoopinterestratelist WHERE interestrate_id = :interestrate_id");						
					if($interestratelist->execute([
							':interestrate_id' =>  $dataComing["interestrate_id"]
						])){
						$arrayResult["RESULT"] = TRUE;
						echo json_encode($arrayResult);
					}else{
						$arrayResult['RESPONSE'] = "ไม่สามารถลบข้อมูลได้ กรุณาติดผู้ผัฒนา";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}  
			
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