<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','question_id','is_use'],$dataComing)){
	
	$del_img = $conmysql->prepare("UPDATE  webcoopquestion SET is_use = :is_use WHERE question_id = :question_id");						
	if($del_img->execute([
			':question_id' =>  $dataComing["question_id"],
			':is_use' =>  $dataComing["is_use"]
		])){
			$del_file="../../../../".$dataComing["file_patch"];
			$del=unlink($del_file);
			
			
			$arrayResult["RESULT"] = TRUE;
			$arrayResult["delFile"] = $del;
			echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถลบไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
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