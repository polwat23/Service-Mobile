<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GenerateQR')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayGrpTrans = array();
		$arrGrpAcc = array();
		$arrGrpCont = array();
		$getTypeTransQR = $conmysql->prepare("SELECT trans_code_qr,trans_desc_qr,operation_desc_th,operation_desc_en FROM gcconttypetransqrcode WHERE is_use = '1'");
		$getTypeTransQR->execute();
		while($rowTypeQR = $getTypeTransQR->fetch(PDO::FETCH_ASSOC)){
			$arrTypeQR = array();
			$arrTypeQR["ALLOW_SELECT_TRANS_MODE"] = TRUE;
			$arrTypeQR["TRANS_CODE"] = $rowTypeQR["trans_code_qr"];
			$arrTypeQR["TRANS_DESC"] = $rowTypeQR["trans_desc_qr"];
			$arrTypeQR["OPERATE_DESC"] = $rowTypeQR["operation_desc_".$lang_locale];
			if($rowTypeQR["trans_code_qr"] == '01'){
				$formatDept = $func->getConstant('dep_format');
				$hiddenFormat = $func->getConstant('hidden_dep');
				$checkCanReceive = $conmysql->prepare("SELECT dept_type_code FROM gcconstantaccountdept WHERE allow_deposit_outside = '1'");
				$checkCanReceive->execute();
				$arrDepttypeAllow = array();
				while($rowCanReceive = $checkCanReceive->fetch(PDO::FETCH_ASSOC)){
					$arrDepttypeAllow[] = $rowCanReceive["dept_type_code"];
				}
				$getAccountinTrans = $conoracle->prepare("SELECT dp.account_no as DEPTACCOUNT_NO,dp.account_name as DEPTACCOUNT_NAME,dp.BALANCE as PRNCBAL, dt.ACC_DESC 
														FROM BK_H_SAVINGACCOUNT dp
														LEFT JOIN BK_M_ACC_TYPE dt ON dp.ACC_TYPE = dt.ACC_TYPE
														WHERE dp.account_id = :member_no and dp.ACC_STATUS = 'O' and dp.ACC_TYPE IN('".implode("','",$arrDepttypeAllow)."')");
				$getAccountinTrans->execute([':member_no' => $member_no]);
				while($rowAccTrans = $getAccountinTrans->fetch(PDO::FETCH_ASSOC)){
					$arrAccTrans = array();
					$arrAccTrans["ACCOUNT_NO"] = $lib->formataccount($rowAccTrans["DEPTACCOUNT_NO"],$formatDept);
					$arrAccTrans["ACCOUNT_NO_HIDE"] = $lib->formataccount_hidden($arrAccTrans["ACCOUNT_NO"],$hiddenFormat);
					$arrAccTrans["ACCOUNT_NAME"] = TRIM($rowAccTrans["DEPTACCOUNT_NAME"]);
					$arrAccTrans["ACCOUNT_TYPE"] = $rowAccTrans["ACC_DESC"];
					$arrAccTrans["PRIN_BAL"] = $rowAccTrans["PRNCBAL"];
					$arrAccTrans["TRANS_CODE"] = $rowTypeQR["trans_code_qr"];
					$arrGrpAcc[] = $arrAccTrans;
				}
			}else if($rowTypeQR["trans_code_qr"] == '02'){
				$checkCanGen = $conmysql->prepare("SELECT loantype_code FROM gcconstanttypeloan WHERE is_qrpayment = '1'");
				$checkCanGen->execute();
				$arrLoantypeAllow = array();
				while($rowCanGen = $checkCanGen->fetch(PDO::FETCH_ASSOC)){
					$arrLoantypeAllow[] = $rowCanGen["loantype_code"];
				}
				$getContract = $conoracle->prepare("SELECT lt.L_TYPE_NAME AS LOAN_TYPE,ln.LCONT_ID as loancontract_no,
											ln.LCONT_AMOUNT_SAL as LOAN_BALANCE,
											ln.LCONT_APPROVE_SAL as APPROVE_AMT,ln.LCONT_DATE as startcont_date,
											ln.LCONT_SAL as PERIOD_PAYMENT,ln.LCONT_MAX_INSTALL as PERIOD,
											ln.LCONT_MAX_INSTALL - ln.LCONT_NUM_INST as LAST_PERIOD,
											ln.LCONT_PAY_LAST_DATE as LAST_OPERATE_DATE
											FROM LOAN_M_CONTACT ln LEFT JOIN LOAN_M_TYPE_NAME lt ON ln.L_TYPE_CODE = lt.L_TYPE_CODE 
											WHERE ln.account_id = :member_no and ln.LCONT_STATUS_CONT IN('H','A') and ln.L_TYPE_CODE IN('".implode("','",$arrLoantypeAllow)."')");
				$getContract->execute([':member_no' => $member_no]);
				while($rowContract = $getContract->fetch(PDO::FETCH_ASSOC)){
					$arrContract = array();
					$contract_no = preg_replace('/\//','',$rowContract["LOANCONTRACT_NO"]);
					$interest = $cal_loan->calculateIntAPI($contract_no);
					$arrContract["ACCOUNT_NO"] = $contract_no;
					$arrContract["ACCOUNT_NO_HIDE"] = $contract_no;
					$arrContract["LOAN_BALANCE"] = number_format($rowContract["LOAN_BALANCE"],2);
					$arrContract["PERIOD"] = $rowContract["LAST_PERIOD"].' / '.$rowContract["PERIOD"];
					$arrContract['LOAN_TYPE'] = $rowContract["LOAN_TYPE"];
					//$arrContract['INT_BALANCE'] = number_format($interest,2);
					$arrContract["TRANS_CODE"] = $rowTypeQR["trans_code_qr"];
					$arrContract["PERIOD_PAYMENT"] = number_format($rowContract["PERIOD_PAYMENT"],2);
					$arrGrpAcc[] = $arrContract;
				}
			}else if($rowTypeQR["trans_code_qr"] == '03'){
				$getSharemasterinfo = $conoracle->prepare("SELECT SHR_SUM_BTH as SHARE_AMT,SHR_BTH as PERIOD_SHARE_AMT,SHR_QTY as sharebegin_amt
															FROM SHR_MEM WHERE account_id = :member_no");
				$getSharemasterinfo->execute([':member_no' => $member_no]);
				$rowMastershare = $getSharemasterinfo->fetch(PDO::FETCH_ASSOC);
				$arrShare = array();
				$arrShare["SHARE_AMT"] = number_format($rowMastershare["SHARE_AMT"],2);
				$arrShare["PERIOD_SHARE_AMT"] = number_format($rowMastershare["PERIOD_SHARE_AMT"],2);
				$arrShare["TRANS_CODE"] = $rowTypeQR["trans_code_qr"];
				$arrGrpAcc[] = $arrShare;
				$arrTypeQR["IS_DESTINATION"] = false;
			}
			$arrayGrpTrans[] = $arrTypeQR;
		}
		$arrayResult["TYPE_TRANS"] = $arrayGrpTrans;
		$arrayResult["CHOOSE_ACCOUNT"] = $arrGrpAcc;
		$arrayResult["DISABLED_MULTISLIP"] = true;
		$arrayResult["RESULT"] = TRUE;
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