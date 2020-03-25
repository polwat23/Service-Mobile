<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username','newusername'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','manageuser')){
		$updaeusername = $conmysql->prepare("UPDATE coreuser 
										  SET username = :newusername
								          WHERE  username = :username;");
		if($updaeusername->execute([
			':newusername' => $dataComing["newusername"],
			':username' => $dataComing["username"]
		])){
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขชื่อเมนูได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
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