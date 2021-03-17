<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){

 $insert_webboard = $conmysql->prepare("INSERT INTO reply(
															question_id,
															detail,
															id_momment_parent,
															name,
															avatar
														)
														VALUES(
															:question_id,
															:detail,
															:id_momment_parent,
															:name,
															:avatar													
														)");
	if($insert_webboard->execute([
		':question_id' =>  $dataComing["question_id"],
		':detail' =>  $dataComing["detail"]?? null,
		':id_momment_parent' =>  $dataComing["id_momment_parent"]?? null,
		':name' =>  $dataComing["name"]?? null,
		':avatar' =>  $dataComing["avatar"]?? null,
		
		
	])){	

		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
							
	}else{
		
		$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['dataComing'] = $dataComing;
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