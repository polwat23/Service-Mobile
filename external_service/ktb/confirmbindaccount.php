<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['sigma_key'],$payload)){
	$getAccBankAllow = $conoracle->prepare("SELECT account_code FROM atmregister WHERE member_no = :member_no and expense_bank = '006' and mobile_flag = '1'");
	$getAccBankAllow->execute([':member_no' => $member_no]);
	$rowAccBank = $getAccBankAllow->fetch(PDO::FETCH_ASSOC);
	if($rowAccBank["account_code"] == $payload["bank_account_no"]){
		$updateBindAcc = $conmysql->prepare("UPDATE gcbindaccount SET bindaccount_status = '1',bind_date = NOW(),
											deptaccount_no_bank = :bank_acc WHERE sigma_key = :sigma_key");
		if($updateBindAcc->execute([
			':sigma_key' => $payload["sigma_key"],
			':bank_acc' => $payload["bank_account_no"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS9006";
			$arrayResult['RESPONSE_MESSAGE'] = "Cannot update status bindaccount";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$updateBindAcc = $conmysql->prepare("UPDATE gcbindaccount SET bindaccount_status = '7',bind_date = NOW(),
											deptaccount_no_bank = :bank_acc WHERE sigma_key = :sigma_key");
		if($updateBindAcc->execute([
			':sigma_key' => $payload["sigma_key"],
			':bank_acc' => $payload["bank_account_no"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS9007";
			$arrayResult['RESPONSE_MESSAGE'] = "Cannot update status bindaccount";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS9004";
	$arrayResult['RESPONSE_MESSAGE'] = "Payload not complete";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>