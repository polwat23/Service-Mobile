<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DividendInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrDivmaster = array();
		$show_pdf = 0;
		$limit_year = $func->getConstant('limit_dividend');
		$getYeardividend = $conmssqlcoop->prepare("select TOP ".$limit_year." pay_no as DIV_YEAR from coPay_Transaction  
												where  member_id = :member_no and type = 'D' 
												group by pay_no
												order by pay_no desc");
		$getYeardividend->execute([
			':member_no' => $member_no
		]);
		while($rowYear = $getYeardividend->fetch(PDO::FETCH_ASSOC)){
			$arrDividend = array();
			$arrDiv = array();
			$arrDetail = array();
			$arrOther = array();
			$arrayRecv = array();
			$getDivMaster = $conmssqlcoop->prepare("select pt.STOCK_ONHAND, pt.STOCK_ONHAND_VALUE ,pt.AMOUNT as  DIV_AMT,pt.pay_no as DIV_YEAR, 
													cb.shortname as BANK,co.bank_account as BANK_ACCOUNT,cpm.RATE,cpm.PAY_DATE,cpm.STATUS
													from coPay_Transaction  pt LEFT  JOIN cocooptation co ON pt.member_id = co.member_id 
													LEFT JOIN coBank cb ON   co.bank_code = cb.bank_code
													LEFT  JOIN coPay_Master cpm ON pt.pay_no = cpm.pay_no AND pt.type = cpm.type
													where  pt.type = 'D' and pt.pay_no = :div_year  and co.member_id = :member_no");
			$getDivMaster->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$rowDiv = $getDivMaster->fetch(PDO::FETCH_ASSOC);
			$arrDividend["YEAR"] = $rowYear["DIV_YEAR"];
			$arrDiv["TEXT_DESC"] = "ปันผล";
			$arrDiv["AMOUNT"] = number_format($rowDiv["DIV_AMT"],2);
			$arrDiv["OTHER_INFO"][0]["LABEL"] = "วันที่จ่ายปันผล";	
			$arrDiv["OTHER_INFO"][0]["VALUE"] =  $lib->convertdate($rowDiv["PAY_DATE"],'d M Y');
			$arrDiv["OTHER_INFO"][1]["LABEL"] = "หุ้น";	
			$arrDiv["OTHER_INFO"][1]["VALUE"] =  number_format($rowDiv["STOCK_ONHAND"],2);
			$arrDiv["OTHER_INFO"][2]["LABEL"] = "มูลค่าหุ้น";	
			$arrDiv["OTHER_INFO"][2]["VALUE"] =  number_format($rowDiv["STOCK_ONHAND_VALUE"],2);
			
			
			
			/*$arrayRecv["TEXT_DESC"] = "วิธีรับเงิน";
			$arrayRecv["BANK"] = $rowDiv["BANK"];	
			$arrayRecv["RECEIVE_DESC"] = "โอนธนาคาร";
			
			if(isset($rowDiv["BANK"])){
				$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount($rowDiv["BANK_ACCOUNT"],'xxx-xxxxxx-x');
			}else{
				$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount($rowDiv["BANK_ACCOUNT"],$func->getConstant('dep_format'));
			}*/
			$arrDividend["DETAIL"][0] = $arrDiv;
			//$arrDividend["DETAIL"][2] = $arrayRecv;
			if($rowDiv["STATUS"] == 'A'){
				$arrDivmaster[] = $arrDividend;
			}
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