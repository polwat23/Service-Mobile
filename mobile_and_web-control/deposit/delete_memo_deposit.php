<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','seq_no','account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositStatement')){
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
			$arrayResult['RESPONSE_CODE'] = "WS1000";
			$arrayResult['RESPONSE_MESSAGE'] = "Delete Memo failed !!";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>