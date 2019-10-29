<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type'],$payload) && $lib->checkCompleteArgument(['menu_component','seq_no','account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'DepositStatement')){
		$account_no = preg_replace('/-/','',$dataComing["account_no"]);
		$DeleteMemoDept = $conmysql->prepare("DELETE FROM gcmemodept WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
		if($DeleteMemoDept->execute([
			':deptaccount_no' => $account_no,
			':seq_no' => $dataComing["seq_no"]
		])){
			$arrayResult['RESULT'] = TRUE;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "5005";
			$arrayResult['RESPONSE_AWARE'] = "delete";
			$arrayResult['RESPONSE'] = "Delete Memo failed !!";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
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