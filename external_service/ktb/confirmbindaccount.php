<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['sigma_key'],$payload)){
	$jsonConfigAS = file_get_contents(__DIR__.'/../../config/config_alias.json');
	$configAS = json_decode($jsonConfigAS,true);
	$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
	$getAccBankAllow = $conoracle->prepare("SELECT account_code FROM atmregistermobile WHERE member_no = :member_no and expense_bank = '006' and appl_status = '1' 
											and connect_status = '1'");
	$getAccBankAllow->execute([':member_no' => $member_no]);
	$rowAccBank = $getAccBankAllow->fetch(PDO::FETCH_ASSOC);
	if(isset($rowAccBank["ACCOUNT_CODE"]) && $rowAccBank["ACCOUNT_CODE"] == $payload["bank_account_no"]){
		$updateBindAcc = $conoracle->prepare("UPDATE gcbindaccount SET bindaccount_status = '1',bind_date = NOW(),
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
		$getAccBankAllowATM = $conoracle->prepare("SELECT account_code FROM atmregister WHERE member_no = :member_no and expense_bank = '006' and appl_status = '1'");
		$getAccBankAllowATM->execute([':member_no' => $member_no]);
		$rowAccBankATM = $getAccBankAllowATM->fetch(PDO::FETCH_ASSOC);
		if(isset($rowAccBankATM["ACCOUNT_CODE"]) && $rowAccBankATM["ACCOUNT_CODE"] == $payload["bank_account_no"]){
			$updateBindAcc = $conoracle->prepare("UPDATE gcbindaccount SET bindaccount_status = '1',bind_date = NOW(),
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
			$updateBindAcc = $conoracle->prepare("UPDATE gcbindaccount SET bindaccount_status = '7',bind_date = NOW(),
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