<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_announce'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','announce')){

		$update_announce = $conmysql->prepare("UPDATE gcannounce SET effect_date = :effect_date
																		WHERE id_announce = :id_announce");
			if($update_announce->execute([
				':id_announce' =>  $dataComing["id_announce"],
				':effect_date' =>  NULL
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);

			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแจ้งประกาศได้ กรุณาติดต่อผู้พัฒนา ";
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