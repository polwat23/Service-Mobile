<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	  
	$del_img = $conmysql->prepare("UPDATE  webcoopbanner SET is_use = :is_use WHERE banner_id = :banner_id");						
	if($del_img->execute([
			':banner_id' =>  $dataComing["banner_id"],
			':is_use' =>  $dataComing["is_use"]
		])){		
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารกทำรายการได้ รุณาติดต่อผู้พัฒนา ";
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