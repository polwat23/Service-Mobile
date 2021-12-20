<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentMonthlyDetail')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$dateshow_kpmonth = $func->getConstant('dateshow_kpmonth');
		$keep_forward = $func->getConstant('process_keep_forward');
		$MonthForCheck = date('m');
		$DayForCheck = date('d');
		$getLastReceive = $conmssql->prepare("SELECT TOP 1 MAX(recv_period) as MAX_RECV,RECEIPT_NO,RECEIVE_AMT
															FROM kptempreceive WHERE member_no = :member_no GROUP BY RECEIPT_NO,RECEIVE_AMT ORDER BY MAX_RECV DESC");
		$getLastReceive->execute([':member_no' => $member_no]);
		$rowLastRecv = $getLastReceive->fetch(PDO::FETCH_ASSOC);
		$checkHasBeenPay = $conmssql->prepare("SELECT RECV_PERIOD FROM kpmastreceive WHERE member_no = :member_no and recv_period = :max_recv and keeping_status = 1");
		$checkHasBeenPay->execute([
			':member_no' => $member_no,
			':max_recv' => $rowLastRecv["MAX_RECV"]
		]);
		$rowBeenPay = $checkHasBeenPay->fetch(PDO::FETCH_ASSOC);
		$max_recv = (int) substr($rowLastRecv["MAX_RECV"],4);
		if($keep_forward == '1'){
			if($MonthForCheck < $max_recv){
				http_response_code(204);
				
			}else{
				if($DayForCheck < $dateshow_kpmonth){
					http_response_code(204);
					
				}
			}
		}else{
			if($DayForCheck < $dateshow_kpmonth){
				http_response_code(204);
				
			}
		}
		if((isset($rowBeenPay["RECV_PERIOD"]) && $rowBeenPay["RECV_PERIOD"] != "") || (empty($rowLastRecv["MAX_RECV"]) && $rowLastRecv["MAX_RECV"] == "")){
			http_response_code(204);
			
		}
		$arrayResult["RECEIVE_AMT"] = number_format($rowLastRecv["RECEIVE_AMT"],2);
		$arrayResult["RECV_PERIOD"] = $rowLastRecv["MAX_RECV"];
		$arrayResult["SLIP_NO"] = $rowLastRecv["RECEIPT_NO"];
		$arrayResult["MONTH_RECEIVE"] = $lib->convertperiodkp(TRIM($rowLastRecv["MAX_RECV"]));
		$getPaymentDetail = $conmssql->prepare("SELECT 
																	CASE kut.system_code 
																	WHEN 'LON' THEN ISNULL(LT.LOANTYPE_DESC,KUT.KEEPITEMTYPE_DESC)
																	WHEN 'DEP' THEN ISNULL(DP.DEPTTYPE_DESC,KUT.KEEPITEMTYPE_DESC)
																	ELSE kut.keepitemtype_desc
																	END as TYPE_DESC,
																	KUT.KEEPITEMTYPE_GRP AS TYPE_GROUP,
																	CASE KUT.KEEPITEMTYPE_GRP 
																		WHEN 'DEP' THEN KPD.DESCRIPTION
																		WHEN 'LON' THEN KPD.LOANCONTRACT_NO
																	ELSE KPD.DESCRIPTION END AS PAY_ACCOUNT,
																	KPD.PERIOD,
																	ISNULL(KPD.ITEM_PAYMENT * KUT.SIGN_FLAG,0) AS ITEM_PAYMENT,
																	ISNULL(KPD.ITEM_BALANCE,0) AS ITEM_BALANCE,
																	ISNULL(KPD.PRINCIPAL_PAYMENT,0) AS PRN_BALANCE,
																	ISNULL(KPD.INTEREST_PAYMENT,0) AS INT_BALANCE
																	FROM KPTEMPRECEIVEDET KPD LEFT JOIN KPUCFKEEPITEMTYPE KUT ON 
																	KPD.KEEPITEMTYPE_CODE = KUT.KEEPITEMTYPE_CODE
																	LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
																	LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
																	WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
																	ORDER BY kut.SORT_IN_RECEIVE ASC");
		$getPaymentDetail->execute([
			':member_no' => $member_no,
			':recv_period' => $rowLastRecv["MAX_RECV"]
		]);
		$arrGroupDetail = array();
		while($rowDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();
			$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
			if($rowDetail["TYPE_GROUP"] == 'SHR'){
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
			}else if($rowDetail["TYPE_GROUP"] == 'LON'){
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขสัญญา';
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
				$arrDetail["PRN_BALANCE"] = number_format($rowDetail["PRN_BALANCE"],2);
				$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
			}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขบัญชี';
			}else if($rowDetail["TYPE_GROUP"] == "OTH"){
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'จ่าย';
			}
			$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
			$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ITEM_PAYMENT"],2);
			$arrGroupDetail[] = $arrDetail;
		}
		$arrayResult['SHOW_SLIP_REPORT'] = TRUE;
		$arrayResult['DETAIL'] = $arrGroupDetail;
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
