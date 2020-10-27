<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_news'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managenews')){
	$updatemenu = $conmysql->prepare("UPDATE  gcnews  SET is_use = '-9'
										  WHERE id_news=:id_news");
		if($updatemenu->execute([
			':id_news' => $dataComing["id_news"]
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