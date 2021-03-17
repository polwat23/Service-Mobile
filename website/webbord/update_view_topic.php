<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){


	$fetchWebboard = $conmysql->prepare("SELECT
												view
											FROM
												question
											WHERE 
												question_id = :question_id
											");
	

	
	$fetchWebboard->execute([':question_id' =>  $dataComing["question_id"]]);
	$rowWebboard = $fetchWebboard->fetch(PDO::FETCH_ASSOC);
	$newView = $rowWebboard["view"]+1;

	$insert_webboard = $conmysql->prepare("update question set view = :view WHERE question_id = :question_id ");
	if($insert_webboard->execute([
		':view' =>  $newView,
		':question_id' =>  $dataComing["question_id"]
		
	])){	
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
							
	}else{
		
		$arrayResult['RESPONSE'] = "มีข้อผิดพลาดบางอย่าง  ";
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