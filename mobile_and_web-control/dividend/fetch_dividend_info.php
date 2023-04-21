<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DividendInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		$arrDivmaster = array();
		$limit_year = $func->getConstant('limit_dividend');
		$getYeardividend = $conoracle->prepare("SELECT account_year AS DIV_YEAR 
												FROM CMCLOSESHLNCOYEAR 
												WHERE TRIM(MEMBER_NO) = :member_no and account_year >= to_number((select max(account_year) from CMCLOSESHLNCOYEAR where TRIM(MEMBER_NO) = :member_no)) - :limit_year
												order by div_year desc");
		$getYeardividend->execute([
			':member_no' => $member_no,
			':limit_year' => $limit_year
		]);
		while($rowYear = $getYeardividend->fetch(PDO::FETCH_ASSOC)){
			convertArray($rowYear,true);
			$arrDividend = array();
			$getDivMaster = $conoracle->prepare("SELECT 
														DEV_PAYMENT AS DIV_AMT,
														AVG_PAYMENT AS  AVG_AMT,
														TOTAL_PAYMENT AS SUMDIV
													FROM 
														CMCLOSESHLNCOYEARDET 
													WHERE 
														TRIM(MEMBER_NO) = :member_no
														AND ACCOUNT_YEAR = :div_year ");
			$getDivMaster->execute([
				':member_no' => $member_no,
				':div_year' =>  $rowYear["DIV_YEAR"]
			]);
			$rowDiv = $getDivMaster->fetch(PDO::FETCH_ASSOC);
			convertArray($rowDiv,true);
			$getRateDiv = $conoracle->prepare("SELECT
													MD.DIV_RATE AS DIVIDEND_RATE,
													MD.AVG_RATE AS AVERAGE_RATE
												FROM 
													CMCLOSESHLNCOYEAR MD LEFT JOIN CMCLOSESHLNCOYEARDET CD  ON CD.ACCOUNT_YEAR = MD.ACCOUNT_YEAR AND TRIM(CD.MEMBER_NO) = TRIM(MD.MEMBER_NO)
												WHERE  TRIM(md.MEMBER_NO) = :member_no
													AND MD.ACCOUNT_YEAR = :div_year");
			$getRateDiv->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$rowRateDiv = $getRateDiv->fetch(PDO::FETCH_ASSOC);
			$arrDividend["YEAR"] = $rowYear["DIV_YEAR"];
			$arrDividend["DIV_RATE"] = ($rowRateDiv["DIVIDEND_RATE"] * 100)." %";
			$arrDividend["AVG_RATE"] = ($rowRateDiv["AVERAGE_RATE"] * 100)." %";
			$arrDividend["DIV_AMT"] = number_format($rowDiv["DIV_AMT"],2);
			$arrDividend["AVG_AMT"] = number_format($rowDiv["AVG_AMT"],2);
			$arrDividend["SUM_AMT"] = number_format($rowDiv["SUMDIV"],2);
			$getMethpay = $conoracle->prepare("SELECT
												MD.EXPENSE_CODE AS TYPE_DESC,
												(SELECT CD.TOTAL_PAYMENT FROM CMCLOSESHLNCOYEARDET CD WHERE  CD.ACCOUNT_YEAR = MD.ACCOUNT_YEAR AND TRIM(CD.MEMBER_NO) = TRIM(MD.MEMBER_NO)) AS RECEIVE_AMT ,						
												MD.EXPENSE_ACCID AS BANK_ACCOUNT,
												CMB.BANK_DESC AS BANK_NAME,
												CMBB.BRANCH_NAME,
												MD.EXPENSE_CODE AS DIVAVG_CODE
											FROM 
												CMCLOSESHLNCOYEAR MD LEFT JOIN CMUCFBANK CMB ON MD.EXPENSE_BANK = CMB.BANK_CODE	
												LEFT JOIN CMUCFBANKBRANCH CMBB ON MD.EXPENSE_BRANCH = CMBB.BRANCH_ID AND
												MD.EXPENSE_BANK = CMBB.BANK_CODE
											WHERE  
												MD.EXPENSE_CODE IN ('CSH','TRS','TRN','CBT') 
												AND TRIM(md.MEMBER_NO) = :member_no
												AND MD.ACCOUNT_YEAR = :div_year");
			$getMethpay->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			while($rowMethpay = $getMethpay->fetch(PDO::FETCH_ASSOC)){
				convertArray($rowMethpay,true);
				$arrayRecv = array();
				$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount($rowMethpay["BANK_ACCOUNT"],'xxx-xxxxxx-x');
				if($rowMethpay["DIVAVG_CODE"] == "CBT"){
					$rowMethpay["TYPE_DESC"] = "โอนเข้าธนาคาร";
				}else if($rowMethpay["DIVAVG_CODE"] == "CSH"){
					$rowMethpay["TYPE_DESC"] = "เงินสด";
				}else if($rowMethpay["DIVAVG_CODE"] == "TRS"){
					$rowMethpay["TYPE_DESC"] = "โอนซื้อหุ้น";
				} else if($rowMethpay["DIVAVG_CODE"] == "TRN"){
					$rowMethpay["TYPE_DESC"] = "โอนเข้าเงินฝาก";
				} else{
					$rowMethpay["TYPE_DESC"] = "";
				} 
				if($rowMethpay["DIVAVG_CODE"] == 'CBT'){
					$arrayRecv["RECEIVE_DESC"] = $rowMethpay["TYPE_DESC"].' '.$rowMethpay["BANK_NAME"];
				}else{
					$arrayRecv["RECEIVE_DESC"] = $rowMethpay["TYPE_DESC"];
				}
				$arrayRecv["RECEIVE_AMT"] = number_format($rowMethpay["RECEIVE_AMT"],2);
				$arrDividend["RECEIVE_ACCOUNT"][] = $arrayRecv;
			}
			$getPaydiv = $conoracle->prepare("SELECT   
												(CASE 
														WHEN DIVAVG_CODE = 'TRP' THEN 'ชำระหนี้สหกรณ์'
														WHEN DIVAVG_CODE = 'TRL' THEN 'กรมบังคับคดี'
														WHEN DIVAVG_CODE = 'TRC' THEN 'ชำระฉุกเฉินเพื่อค่าครองชีพ'
														WHEN DIVAVG_CODE = 'TWF' THEN 'หักเงินฝากสำรอง'
												ELSE '' END) AS TYPE_DESC,       
												TRANSPAY_AMT AS PAY_AMT
												FROM MBDIVAVGTRNPOST 
												WHERE ( TRIM(MEMBER_NO) = :member_no) AND
												DIVAVG_CODE IN ('TRP','TRL','TRC','TWF')  
												AND ( DIVAVG_YEAR = :div_year )
												ORDER BY SEQ_NO");
			$getPaydiv->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$arrayPayGroup = array();
			$sumPay = 0;
			
			while($rowPay = $getPaydiv->fetch(PDO::FETCH_ASSOC)){
				convertArray($rowPay,true);
				$arrPay = array();
				$arrPay["TYPE_DESC"] = $rowPay["TYPE_DESC"];
				$arrPay["PAY_AMT"] = number_format($rowPay["PAY_AMT"],2);
				$sumPay += $rowPay["PAY_AMT"];
				$arrayPayGroup[] = $arrPay;
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