<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no','member_type'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','assignadmin')){
			$updatemenu = $conmysql->prepare("UPDATE gcmemberaccount SET user_type = :member_type
										 WHERE member_no = :member_no");
			if($updatemenu->execute([
				':member_no' => $dataComing["member_no"]
			])){
				$arrayResult["RESULT"] = TRUE;
			}else{
				$arrayResult["RESULT"] = FALSE;
			}
			echo json_encode($arrayResult);
			
		
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>
