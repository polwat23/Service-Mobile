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
			$arrTypeQR["TRANS_CODE"] = $rowTypeQR["trans_code_qr"];
			$arrTypeQR["TRANS_DESC"] = $rowTypeQR["trans_desc_qr"];
			$arrTypeQR["OPERATE_DESC"] = $rowTypeQR["operation_desc_".$lang_locale];
			
			if($rowTypeQR["trans_code_qr"] == '001'){
				$formatDept = $func->getConstant('dep_format');
				$arrDepttypeAllow  = array();
				$hiddenFormat = $func->getConstant('hidden_dep');
				$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
															LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
															WHERE gat.member_no = :member_no and gat.is_use = '1' and gad.allow_deposit_outside = '1'");
				$fetchAccAllowTrans->execute([':member_no' => $payload["member_no"]]);
				while($rowAccAllow = $fetchAccAllowTrans->fetch(PDO::FETCH_ASSOC)){
					$arrDepttypeAllow [] = "'".$rowAccAllow["deptaccount_no"]."'";
				}
				$getAccountinTrans = $conoracle->prepare("SELECT DEPTACCOUNT_NO,DEPTACCOUNT_NAME,PRNCBAL FROM dpdeptmaster 
															WHERE deptclose_status <> 1 and deptaccount_no IN(".implode(",",$arrDepttypeAllow).")");
				$getAccountinTrans->execute();
				while($rowAccTrans = $getAccountinTrans->fetch(PDO::FETCH_ASSOC)){
					$arrAccTrans = array();
					$arrAccTrans["ACCOUNT_NO"] = $lib->formataccount($rowAccTrans["DEPTACCOUNT_NO"],$formatDept);
					$arrAccTrans["ACCOUNT_NO_HIDE"] = $lib->formataccount_hidden($arrAccTrans["ACCOUNT_NO"],$hiddenFormat);
					$arrAccTrans["ACCOUNT_NAME"] = TRIM($rowAccTrans["DEPTACCOUNT_NAME"]);
					$arrAccTrans["PRIN_BAL"] = $rowAccTrans["PRNCBAL"];
					$arrAccTrans["TRANS_CODE"] = $rowTypeQR["trans_code_qr"];
					$arrGrpAcc[] = $arrAccTrans;
				}
			}else if($rowTypeQR["trans_code_qr"] == '002'){
				$checkCanGen = $conmysql->prepare("SELECT loantype_code FROM gcconstanttypeloan WHERE is_qrpayment = '1'");
				$checkCanGen->execute();
				$arrLoantypeAllow = array();
				while($rowCanGen = $checkCanGen->fetch(PDO::FETCH_ASSOC)){
					$arrLoantypeAllow[] = $rowCanGen["loantype_code"];
				}
				$getContract = $conoracle->prepare("SELECT lt.LOANTYPE_DESC AS LOAN_TYPE,ln.loancontract_no,ln.principal_balance as LOAN_BALANCE,
											ln.loanapprove_amt as APPROVE_AMT,ln.startcont_date,ln.period_payment,ln.period_payamt as PERIOD,
											ln.LAST_PERIODPAY as LAST_PERIOD,
											(SELECT max(operate_date) FROM lncontstatement WHERE loancontract_no = ln.loancontract_no) as LAST_OPERATE_DATE
											FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
											WHERE ln.member_no = :member_no and ln.contract_status > 0 and ln.contract_status <> 8 and ln.LOANTYPE_CODE IN('".implode("','",$arrLoantypeAllow)."')");
				$getContract->execute([':member_no' => $member_no]);
				while($rowContract = $getContract->fetch(PDO::FETCH_ASSOC)){
					$arrContract = array();
					$contract_no = preg_replace('/\//','',$rowContract["LOANCONTRACT_NO"]);
					$arrContract["ACCOUNT_NO"] = $contract_no;
					$arrContract["ACCOUNT_NO_HIDE"] = $contract_no;
					$arrContract["LOAN_BALANCE"] = number_format($rowContract["LOAN_BALANCE"],2);
					$arrContract["PERIOD"] = $rowContract["LAST_PERIOD"].' / '.$rowContract["PERIOD"];
					$arrContract['ACCOUNT_NAME'] = $rowContract["LOAN_TYPE"];
					$arrContract['INT_BALANCE'] = 0;
					$arrContract["TRANS_CODE"] = $rowTypeQR["trans_code_qr"];
					$arrGrpAcc[] = $arrContract;
				}
			}else{
				$arrTypeQR["IS_DESTINATION"] = FALSE;
			}
			$arrayGrpTrans[] = $arrTypeQR;
		}
		$arrayResult["TYPE_TRANS"] = $arrayGrpTrans;
		$arrayResult["CHOOSE_ACCOUNT"] = $arrGrpAcc;
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