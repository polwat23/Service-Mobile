<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','announce')){
		
		$insert_announce = $conmysql->prepare("INSERT INTO gcannounce (announce_title,announce_detail,priority,username)
						  VALUES (:announce_title,:announce_detail,:priority,:username)");
			if($insert_announce->execute([
				':announce_title' =>  $dataComing["announce_title"],
				':announce_detail' =>  $dataComing["announce_detail"],
				':priority' =>  $dataComing["priority"],
				':username' => $dataComing["username"]
		
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