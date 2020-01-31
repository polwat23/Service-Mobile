<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username','newpassword'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','manageuser')){
		$updatepassword = $conmysql->prepare("UPDATE coreuser 
										  SET password = :newpassword
								          WHERE  username = :username;");
		$new_password = password_hash($dataComing["newpassword"], PASSWORD_DEFAULT);								  
		if($updatepassword->execute([
			':newpassword' => $new_password,
			':username' => $dataComing["username"]
		])){
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเปลี่ยนรหัสผ่านได้ กรุณาติดต่อผู้พัฒนา";
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