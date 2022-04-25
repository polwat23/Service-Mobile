<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','branch_name'],$dataComing)){
	
	$inserGropubranch = $conmysql->prepare("INSERT INTO webcoopbranch(
                                        branch_name,                                  
										create_by,
										update_by)
									VALUES(
                                        :branch_name,                               
										:create_by,
										:update_by
									)");
	if($inserGropubranch->execute([
        ':branch_name' =>  $dataComing["branch_name"],
		':create_by' =>  $payload["username"],
		':update_by' =>  $payload["username"]
	])){
	    $arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['RESULT'] = FALSE;
		$arrayResult['insertGropubranch'] = $insertGropubranch;
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

