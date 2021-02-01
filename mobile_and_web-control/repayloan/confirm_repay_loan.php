<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepPayLoan')){
		$deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$arrInitDep = $cal_dep->initDept($deptaccount_no,$dataComing["amt_transfer"],'WTX');
		if($arrInitDep["RESULT"]){
			$arrRightDep = $cal_dep->depositCheckWithdrawRights($deptaccount_no,$dataComing["amt_transfer"],$dataComing["menu_component"]);
			if($arrRightDep["RESULT"]){
				if(isset($arrInitDep["PENALTY_AMT"]) && $arrInitDep["PENALTY_AMT"] > 0){
					$arrayCaution['RESPONSE_MESSAGE'] = $configError["CAUTION_WITHDRAW"][0][$lang_locale];
					$arrayCaution['CANCEL_TEXT'] = $configError["BUTTON_TEXT"][0]["CANCEL_TEXT"][0][$lang_locale];
					$arrayCaution['CONFIRM_TEXT'] = $configError["BUTTON_TEXT"][0]["CONFIRM_TEXT"][0][$lang_locale];
					$arrayResult['CAUTION'] = $arrayCaution;
					$arrayResult['FEE_AMT'] = $arrInitDep["PENALTY_AMT"];
					$arrayResult['FEE_AMT_FORMAT'] = number_format($arrInitDep["PENALTY_AMT"],2);
				}
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = $arrRightDep["RESPONSE_CODE"];
				if($arrRightDep["RESPONSE_CODE"] == 'WS0056'){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($arrRightDep["MINWITD_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = $arrRightDep["RESPONSE_CODE"];
			if($arrRightDep["RESPONSE_CODE"] == 'WS0056'){
				$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($arrRightDep["MINWITD_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
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
	require_once('../../include/exit_footer.php');
	
}
?>