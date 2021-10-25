<?php
require_once('../autoload.php'); 

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ShareInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getSharemasterinfo = $conmssqlcoop->prepare("SELECT top 1  (co.member_stock * 10) as SHARE_AMT,
													csk.value_per_period as VALUESHARE_PERIOD,csk.stock_per_period as STOCKSHARE_PERIOD,
													(CASE WHEN PAYDATE_1 IS NULL OR PAYDATE_1 = 0 THEN 0 ELSE 1 END
													+
													CASE WHEN PAYDATE_2 IS NULL OR PAYDATE_2 = 0 THEN 0 ELSE 1 END
													+
													CASE WHEN PAYDATE_3 IS NULL OR PAYDATE_3 = 0 THEN 0 ELSE 1 END
													+
													CASE WHEN PAYDATE_4 IS NULL OR PAYDATE_4 = 0 THEN 0 ELSE 1 END
													+
													CASE WHEN PAYDATE_5 IS NULL OR PAYDATE_5 = 0 THEN 0 ELSE 1 END)
													AS COUNT_PAID
													FROM cocooptation co LEFT JOIN coreceipt cp ON co.member_id  = cp.member_id
													LEFT JOIN coStockmember csk ON co.member_id = csk.member_id
													LEFT JOIN cocompany com ON co.COMPANY = com.COMPANY
													WHERE co.member_id = :member_no and cp.type ='10'
													order by receipt_no  desc");
		$getSharemasterinfo->execute([':member_no' => $member_no]);
		$rowMastershare = $getSharemasterinfo->fetch(PDO::FETCH_ASSOC);
		if($rowMastershare){
			$arrGroupStm = array();
			//$arrayResult['BRING_FORWARD'] = number_format($rowMastershare["SHAREBEGIN_AMT"] * 10,2);
			$arrayResult['SHARE_AMT'] = number_format($rowMastershare["SHARE_AMT"],2);
			//$arrayResult['PERIOD_SHARE_AMT'] = number_format($rowMastershare["PERIOD_SHARE_AMT"],2);
			$arrOptionHeader[0]["LABEL"] = "จำนวนหุ้นต่องวด";
			$arrOptionHeader[0]["VALUE"] = number_format($rowMastershare["STOCKSHARE_PERIOD"],2);
			$arrOptionHeader[1]["LABEL"] = "จำนวนเงินต่องวด";
			$arrOptionHeader[1]["VALUE"] = number_format($rowMastershare["VALUESHARE_PERIOD"],2);
			$arrOptionHeader[2]["LABEL"] = "จำนวนครั้งในการจ่ายต่อเดือน";
			$arrOptionHeader[2]["VALUE"] = $rowMastershare["COUNT_PAID"];
			$arrayResult["OPTION_INFO_HEADER"] = $arrOptionHeader;
			$limit = $func->getConstant('limit_stmshare');
			$arrayResult['LIMIT_DURATION'] = $limit;
			if($lib->checkCompleteArgument(["date_start"],$dataComing)){
				$date_before = $lib->convertdate($dataComing["date_start"],'y-n-d');
			}else{
				$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
			}
			if($lib->checkCompleteArgument(["date_end"],$dataComing)){
				$date_now = $lib->convertdate($dataComing["date_end"],'y-n-d');
			}else{
				$date_now = date('Y-m-d');
			}
			$getShareStatement = $conmssqlcoop->prepare("SELECT stm.paydate as OPERATE_DATE,(stm.stock * 10) as PERIOD_SHARE_AMOUNT,
														stm.stock_onhand as SUM_SHARE_AMT,crt.description as SHRITEMTYPE_DESC,stm.receipt_no as REF_SLIPNO
														FROM coreceipt stm LEFT JOIN coReceiptType crt ON stm.type = crt.type
														WHERE stm.member_id = ? and stm.type  = '10'  and stm.status ='2'
														and stm.paydate BETWEEN CONVERT(varchar, ? , 23) and CONVERT(varchar, ? , 23) 
														ORDER BY stm.paydate DESC");
			$getShareStatement->execute([$member_no, $date_before,$date_now]);
			while($rowStm = $getShareStatement->fetch(PDO::FETCH_ASSOC)){
				$arrayStm = array();
				$arrayStm["OPERATE_DATE"] = $lib->convertdate($rowStm["OPERATE_DATE"],'D m Y');
				$arrayStm["PERIOD_SHARE_AMOUNT"] = number_format($rowStm["PERIOD_SHARE_AMOUNT"],2);
				$arrayStm["SUM_SHARE_AMT"] = number_format($rowStm["SUM_SHARE_AMT"],2);
				$arrayStm["SHARETYPE_DESC"] = $rowStm["SHRITEMTYPE_DESC"];
				//$arrayStm["PERIOD"] = $rowStm["PERIOD"];
				$arrayStm["SLIP_NO"] = $rowStm["REF_SLIPNO"];
				$arrGroupStm[] = $arrayStm;
			}
			$arrayResult['STATEMENT'] = $arrGroupStm;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			http_response_code(204);
			
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