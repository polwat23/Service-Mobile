<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepBuyShare')){
		$is_separate = $func->getConstant("separate_limit_amount_trans_online");
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getMembType = $conoracle->prepare("SELECT MEMBCAT_CODE FROM mbmembmaster WHERE member_no = :member_no");
		$getMembType->execute([':member_no' => $member_no]);
		$rowMembType = $getMembType->fetch(PDO::FETCH_ASSOC);
		$getCurrShare = $conoracle->prepare("SELECT SHARESTK_AMT FROM shsharemaster WHERE member_no = :member_no");
		$getCurrShare->execute([':member_no' => $member_no]);
		$rowCurrShare = $getCurrShare->fetch(PDO::FETCH_ASSOC);
		$sharereq_value = ($rowCurrShare["SHARESTK_AMT"] * 10) + $dataComing["amt_transfer"];
		$getLimitAllDay = $conoracle->prepare("SELECT total_limit FROM atmucftranslimit WHERE tran_desc = 'MCOOP' and tran_status = 1");
		$getLimitAllDay->execute();
		$rowLimitAllDay = $getLimitAllDay->fetch(PDO::FETCH_ASSOC);
		if($is_separate){
			$getSumAllDay = $conoracle->prepare("SELECT NVL(SUM(DEPTITEM_AMT),0) AS SUM_AMT FROM DPDEPTSTATEMENT 
												WHERE TO_CHAR(OPERATE_DATE,'YYYY-MM-DD') = TO_CHAR(SYSDATE,'YYYY-MM-DD') 
												and ITEM_STATUS = '1' and entry_id IN('MCOOP','ICOOP') and SUBSTR(deptitemtype_code,0,1) = 'W'");
		}else{
			$getSumAllDay = $conoracle->prepare("SELECT NVL(SUM(DEPTITEM_AMT),0) AS SUM_AMT FROM DPDEPTSTATEMENT 
												WHERE TO_CHAR(OPERATE_DATE,'YYYY-MM-DD') = TO_CHAR(SYSDATE,'YYYY-MM-DD') 
												and ITEM_STATUS = '1' and entry_id IN('MCOOP','ICOOP')");
		}
		$getSumAllDay->execute();
		$rowSumAllDay = $getSumAllDay->fetch(PDO::FETCH_ASSOC);
		if(($rowSumAllDay["SUM_AMT"] + $dataComing["amt_transfer"]) > $rowLimitAllDay["TOTAL_LIMIT"]){
			$arrayResult["RESPONSE_CODE"] = 'WS0043';
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		if($rowMembType["MEMBCAT_CODE"] == '10'){
			$getConstantShare = $conoracle->prepare("SELECT MAXSHARE_HOLD,SHAREROUND_FACTOR FROM SHSHARETYPE WHERE SHARETYPE_CODE = '01'");
			$getConstantShare->execute();
			$rowContShare = $getConstantShare->fetch(PDO::FETCH_ASSOC);
			if($sharereq_value > $rowContShare["MAXSHARE_HOLD"]){
				$arrayResult['RESPONSE_CODE'] = "WS0075";
				if(isset($configError["BUY_SHARES_ERR"][0]["0001"][0][$lang_locale])){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${MAXHOLD_AMT}',number_format($rowContShare["MAXSHARE_HOLD"],2),$configError["BUY_SHARES_ERR"][0]["0001"][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			if($sharereq_value < $rowContShare["SHAREROUND_FACTOR"]){
				$arrayResult['RESPONSE_CODE'] = "WS0075";
				if(isset($configError["BUY_SHARES_ERR"][0]["0003"][0][$lang_locale])){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${SHAREROUND_FACTOR}',number_format($rowContShare["SHAREROUND_FACTOR"],2),$configError["BUY_SHARES_ERR"][0]["0003"][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$getConstantShare = $conoracle->prepare("SELECT MAXSHARE_HOLD,SHAREROUND_FACTOR FROM SHSHARETYPE WHERE SHARETYPE_CODE = '02'");
			$getConstantShare->execute();
			$rowContShare = $getConstantShare->fetch(PDO::FETCH_ASSOC);
			if($sharereq_value > $rowContShare["MAXSHARE_HOLD"]){
				$arrayResult['RESPONSE_CODE'] = "WS0075";
				if(isset($configError["BUY_SHARES_ERR"][0]["0001"][0][$lang_locale])){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${MAXHOLD_AMT}',number_format($rowContShare["MAXSHARE_HOLD"],2),$configError["BUY_SHARES_ERR"][0]["0001"][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			if($sharereq_value < $rowContShare["SHAREROUND_FACTOR"]){
				$arrayResult['RESPONSE_CODE'] = "WS0075";
				if(isset($configError["BUY_SHARES_ERR"][0]["0003"][0][$lang_locale])){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${SHAREROUND_FACTOR}',number_format($rowContShare["SHAREROUND_FACTOR"],2),$configError["BUY_SHARES_ERR"][0]["0003"][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}
		// $arrayResult['PENALTY_AMT'] = $amt_transfer;
		// $arrayResult['PENALTY_AMT_FORMAT'] = number_format($amt_transfer,2);
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
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