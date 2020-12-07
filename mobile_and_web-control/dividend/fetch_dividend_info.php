<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DividendInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		$arrDivmaster = array();
		$limit_year = $func->getConstant('limit_dividend');
		$getSequestDiv = $conoracle->prepare("SELECT SEQUEST_DIVAVG FROM mbmembmaster WHERE member_no = :member_no");
		$getSequestDiv->execute([':member_no' => $member_no]);
		$rowSeqDiv = $getSequestDiv->fetch(PDO::FETCH_ASSOC);
		$getYeardividend = $conoracle->prepare("SELECT * FROM (SELECT divavg_year AS DIV_YEAR FROM mbdivavgtemp WHERE TRIM(MEMBER_NO) = :member_no 
												AND branch_id = :branch_id
												GROUP BY divavg_year ORDER BY divavg_year DESC) where rownum <= :limit_year");
		$getYeardividend->execute([
			':member_no' => $member_no,
			':limit_year' => $limit_year,
			':branch_id' => $payload["branch_id"]
		]);
		while($rowYear = $getYeardividend->fetch(PDO::FETCH_ASSOC)){
			$arrDividend = array();
			$getDivMaster = $conoracle->prepare("SELECT 
												DIVIDEND_AMT AS DIV_AMT,
												AVERAGE_AMT AS  AVG_AMT,
												DIVIDEND_AMT+AVERAGE_AMT AS SUMDIV
											FROM 
												mbdivavgtemp 
											WHERE 
												TRIM(MEMBER_NO) = :member_no
												AND DIVAVG_YEAR = :div_year
												AND branch_id = :branch_id");
			$getDivMaster->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"],
				':branch_id' => $payload["branch_id"]
			]);
			$rowDiv = $getDivMaster->fetch(PDO::FETCH_ASSOC);
			$arrDividend["YEAR"] = $rowYear["DIV_YEAR"];
			$arrDividend["DIV_AMT"] = number_format($rowDiv["DIV_AMT"],2);
			$arrDividend["AVG_AMT"] = number_format($rowDiv["AVG_AMT"],2);
			$arrDividend["SUM_AMT"] = number_format($rowDiv["SUMDIV"],2);
			$getMethpay = $conoracle->prepare("SELECT
												(CASE WHEN md.EXPENSE_CODE = 'CSH' THEN 'เงินสด'
														WHEN md.EXPENSE_CODE = 'TRS' THEN 'โอนซื้อหุ้น'
														WHEN md.EXPENSE_CODE = 'TRN' THEN 'โอนเข้าบัญชีเงินฝาก'
														WHEN md.EXPENSE_CODE = 'CBT' THEN 'โอนเข้าบัญชีธนาคาร'
												ELSE '' END) AS TYPE_DESC,
												md.DIVIDEND_AMT+md.AVERAGE_AMT AS RECEIVE_AMT ,						
												md.EXPENSE_ACCID AS BANK_ACCOUNT,
												cmb.BANK_DESC as BANK_NAME,
												md.TEST_FLAG,
												md.RECEIVE_STATUS,
												md.SSOT_AMT,
												md.SSOT_AMT_FEEYEAR,
												md.SSOT_MSV,
												md.SSOT_MSV_FEEYEAR,
												md.SSOT_FTSC,
												md.SSOT_FTSC_FEEYEAR,
												cmbb.BRANCH_NAME
											FROM 
												mbdivavgtemp md LEFT JOIN cmucfbank cmb ON md.EXPENSE_BANK = cmb.bank_code	
												LEFT JOIN cmucfbankbranch cmbb ON md.EXPENSE_BRANCH = cmbb.branch_id and
												md.EXPENSE_BANK = cmbb.bank_code
											WHERE  
												md.EXPENSE_CODE IN ('CSH','TRS','TRN','CBT') AND 
												TRIM(md.MEMBER_NO) = :member_no
												AND md.DIVAVG_YEAR = :div_year
												AND md.TEST_FLAG = '0'
											AND md.branch_id = :branch_id");
			$getMethpay->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"],
				':branch_id' => $payload["branch_id"]
			]);
			while($rowMethpay = $getMethpay->fetch(PDO::FETCH_ASSOC)){
				$arrayRecv = array();
				if($rowSeqDiv["SEQUEST_DIVAVG"] == "1"){
					$arrayRecv["NOTE_RECEIVE"] = "ถูกอายัดปันผล กรุณาติดต่อสหกรณฯ";
					$arrayRecv["NOTE_TEXT_COLOR"] = "red";
				}else{
					if(isset($rowMethpay["BANK_ACCOUNT"])){
						$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount_hidden($lib->formataccount($rowMethpay["BANK_ACCOUNT"],'xxx-xxxxxx-x'),'hhh-hhxxxx-h');
					}
					if($rowMethpay["DIVAVG_CODE"] == 'CBT'){
						$arrayRecv["RECEIVE_DESC"] = $rowMethpay["TYPE_DESC"].' '.$rowMethpay["BANK_NAME"].' '.$rowMethpay["BRANCH_NAME"];
					}else{
						$arrayRecv["RECEIVE_DESC"] = $rowMethpay["TYPE_DESC"];
					}
					$arrayRecv["RECEIVE_STATUS"] = $rowMethpay["RECEIVE_STATUS"];
				}
				//รายการหัก
				$sumPay = 0;
				$arrayPayGroup = array();
				if($rowMethpay["SSOT_AMT"]+$rowMethpay["SSOT_AMT_FEEYEAR"] > 0){
					$arrayPay = array();
					$arrayPay["TYPE_DESC"] = "หัก สสอค.";
					$arrayPay["PAY_AMT"] = number_format($rowMethpay["SSOT_AMT"]+$rowMethpay["SSOT_AMT_FEEYEAR"],2);
					$sumPay += ($rowMethpay["SSOT_AMT"]+$rowMethpay["SSOT_AMT_FEEYEAR"]);
					$arrayPayGroup[] = $arrayPay;
				}
				if($rowMethpay["SSOT_MSV"] + $rowMethpay["SSOT_MSV_FEEYEAR"] > 0){
					$arrayPay = array();
					$arrayPay["TYPE_DESC"] = "หัก สสอ.มศว";
					$arrayPay["PAY_AMT"] = number_format($rowMethpay["SSOT_MSV"] + $rowMethpay["SSOT_MSV_FEEYEAR"],2);
					$sumPay += ($rowMethpay["SSOT_MSV"] + $rowMethpay["SSOT_MSV_FEEYEAR"]);
					$arrayPayGroup[] = $arrayPay;
				}
				if($rowMethpay["SSOT_FTSC"] + $rowMethpay["SSOT_FTSC_FEEYEAR"] > 0){
					$arrayPay = array();
					$arrayPay["TYPE_DESC"] = "หัก สส. ชสอ.";
					$arrayPay["PAY_AMT"] = number_format($rowMethpay["SSOT_FTSC"] + $rowMethpay["SSOT_FTSC_FEEYEAR"],2);
					$sumPay += ($rowMethpay["SSOT_FTSC"] + $rowMethpay["SSOT_FTSC_FEEYEAR"]);
					$arrayPayGroup[] = $arrayPay;
				}
				$arrayRecv["RECEIVE_AMT"] = number_format($rowMethpay["RECEIVE_AMT"] - $sumPay,2);
				$arrDividend["RECEIVE_ACCOUNT"][] = $arrayRecv;
			}
			$arrDividend["PAY"] = $arrayPayGroup;
			$arrDividend["SUMPAY"] = number_format($sumPay,2);
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
