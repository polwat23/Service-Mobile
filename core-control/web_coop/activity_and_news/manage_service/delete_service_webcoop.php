<?php
require_once('../../../autoload.php');


if($lib->checkCompleteArgument(['unique_id','id_service'],$dataComing)){
	
	$update_news_web_coop = $conmysql->prepare("UPDATE webcoopservice SET 
												is_use= :is_use
											WHERE service_id = :id_service
										");
	if($update_news_web_coop->execute([
		':id_service' =>  $dataComing["id_service"],
		':is_use' =>  '-9'
	])){
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถทำรายการได้ กรุณาติดต่อผู้พัฒนา  ";
		$arrayResult['RESULT'] = FALSE;
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