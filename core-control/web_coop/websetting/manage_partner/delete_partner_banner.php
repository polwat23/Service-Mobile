<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','webcooppartner_id'],$dataComing)){
	
	$del_partner = $conmysql->prepare("UPDATE webcooppartner SET is_use ='-9' WHERE webcooppartner_id = :webcooppartner_id");						
	if($del_partner->execute([
			':webcooppartner_id' =>  $dataComing["webcooppartner_id"]
		])){
		//$imgPath = $dataComing["imgPath"];
		//$del_file="../../../../".$imgPath;
		//unlink($del_file);
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถทำรายการได้ กรุณาติดต่อผู้พัฒนา ";
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