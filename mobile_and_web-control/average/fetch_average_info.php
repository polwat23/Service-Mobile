<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DividendAverageInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrDivmaster = array();
		$show_pdf = 0;
		$limit_year = $func->getConstant('limit_dividend');
		$getYeardividend = $conmssqlcoop->prepare("select TOP ".$limit_year." pay_no as DIV_YEAR from coPay_Transaction  where  member_id = :member_no and type = 'A' 
												group by pay_no
												order by pay_no desc");
		$getYeardividend->execute([
			':member_no' => $member_no
		]);
		while($rowYear = $getYeardividend->fetch(PDO::FETCH_ASSOC)){
			$arrDividend = array();
			$arrAvg = array();
			$arrDetail = array();
			$arrOther = array();
			$arrayRecv = array();
			
			//เฉลี่ยคืน
			$getAvgMaster = $conmssqlcoop->prepare("select pc.paramater2 as REMARK,ct.interest as INTEREST_AMT,ct.amount as AVG_AMT,
										pc.paramater3 as AVG_RATE
										from coPay_Transaction ct LEFT JOIN coPay_TransactionCalculate pc ON ct.member_id = pc.member_id 
										AND ct.pay_no = pc.pay_no AND ct.type = pc.type
										LEFT JOIN coPay_Master cpm ON ct.pay_no = cpm.pay_no AND ct.type = cpm.type
										where ct.type ='A' AND ct.pay_no = :div_year AND ct.member_id = :member_no");
			$getAvgMaster->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$rowAvg = $getAvgMaster->fetch(PDO::FETCH_ASSOC);
			$arrDividend["YEAR"] = $rowYear["DIV_YEAR"];
			$arrAvg["TEXT_DESC"] = "เฉลี่ยคืน";
			$arrAvg["AMOUNT"] = number_format($rowAvg["AVG_AMT"],2);
			$arrAvg["OTHER_INFO"][0]["LABEL"] = $rowAvg["REMARK"] ?? "-";	
			$arrAvg["OTHER_INFO"][0]["VALUE"] =  number_format($rowAvg["INTEREST_AMT"],2);
			$arrAvg["OTHER_INFO"][1]["LABEL"] = "อัตราเฉลี่ยคืน";	
			$arrAvg["OTHER_INFO"][1]["VALUE"] =  $rowAvg["AVG_RATE"] * 100 ." %";
			
			/*$arrayRecv["TEXT_DESC"] = "วิธีรับเงิน";
			$arrayRecv["BANK"] = $rowDiv["BANK"];	
			$arrayRecv["RECEIVE_DESC"] = "โอนธนาคาร";
			
			if(isset($rowDiv["BANK"])){
				$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount($rowDiv["BANK_ACCOUNT"],'xxx-xxxxxx-x');
			}else{
				$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount($rowDiv["BANK_ACCOUNT"],$func->getConstant('dep_format'));
			}*/
			$arrDividend["DETAIL"][0] = $arrAvg;
			//$arrDividend["DETAIL"][2] = $arrayRecv;
			$arrDivmaster[] = $arrDividend;
		}
		$arrayResult["DIVIDEND"] = $arrDivmaster;
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