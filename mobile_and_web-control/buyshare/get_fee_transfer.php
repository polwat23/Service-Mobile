<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepBuyShare')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		/*$getMembType = $conoracle->prepare("SELECT MEMBCAT_CODE FROM mbmembmaster WHERE member_no = :member_no");
		$getMembType->execute([':member_no' => $member_no]);
		$rowMembType = $getMembType->fetch(PDO::FETCH_ASSOC);
		$getCurrShare = $conoracle->prepare("SELECT SHARESTK_AMT FROM shsharemaster WHERE member_no = :member_no");
		$getCurrShare->execute([':member_no' => $member_no]);
		$rowCurrShare = $getCurrShare->fetch(PDO::FETCH_ASSOC);
		$sharereq_value = ($rowCurrShare["SHARESTK_AMT"] * 10) + $dataComing["amt_transfer"];
		if($rowMembType["MEMBCAT_CODE"] == '10'){
			$getConstantShare = $conoracle->prepare("SELECT MAXHOLD_SHARE FROM SHSHARETYPE WHERE SHARETYPE_CODE = '01'");
			$getConstantShare->execute();
			$rowContShare = $getConstantShare->fetch(PDO::FETCH_ASSOC);
			if($sharereq_value > $rowContShare["MAXHOLD_SHARE"]){
				$arrayResult['RESPONSE_CODE'] = "WS0075";
				if(isset($configError["BUY_SHARES_ERR"][0]["0001"][0][$lang_locale])){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${MAXHOLD_AMT}',number_format($rowContShare["MAXHOLD_SHARE"],2),$configError["BUY_SHARES_ERR"][0]["0001"][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$getConstantShare = $conoracle->prepare("SELECT MAXHOLD_SHARE FROM SHSHARETYPE WHERE SHARETYPE_CODE = '02'");
			$getConstantShare->execute();
			$rowContShare = $getConstantShare->fetch(PDO::FETCH_ASSOC);
			if($sharereq_value > $rowContShare["MAXHOLD_SHARE"]){
				$arrayResult['RESPONSE_CODE'] = "WS0075";
				if(isset($configError["BUY_SHARES_ERR"][0]["0001"][0][$lang_locale])){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${MAXHOLD_AMT}',number_format($rowContShare["MAXHOLD_SHARE"],2),$configError["BUY_SHARES_ERR"][0]["0001"][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}*/
		// $arrayResult['PENALTY_AMT'] = $amt_transfer;
		// $arrayResult['PENALTY_AMT_FORMAT'] = number_format($amt_transfer,2);
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);

	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>