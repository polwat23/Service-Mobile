<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_webcoopnews'],$dataComing)){
	
	$update_news_web_coop = $conmysql->prepare("UPDATE webcoopnews SET 
												is_use= :is_use
											WHERE id_webcoopnews = :id_webcoopnews
										");
	if($update_news_web_coop->execute([
		':id_webcoopnews' =>  $dataComing["id_webcoopnews"],
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