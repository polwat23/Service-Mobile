<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DividendInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		$arrDivmaster = array();
		$limit_year = $func->getConstant('limit_dividend');
		$getYeardividend = $conoracle->prepare("SELECT * FROM (SELECT cm.account_year AS DIV_YEAR FROM mbdivavgtrnpost mp LEFT JOIN cmaccountyear cm 
														ON mp.DIVAVG_YEAR = cm.account_year WHERE TRIM(mp.MEMBER_NO) = :member_no and cm.SHOWDIVAVG_FLAG = '1' 
														GROUP BY cm.account_year ORDER BY cm.account_year DESC) where rownum <= :limit_year");
		$getYeardividend->execute([
			':member_no' => $member_no,
			':limit_year' => $limit_year
		]);
		while($rowYear = $getYeardividend->fetch(PDO::FETCH_ASSOC)){
			$arrDividend = array();
			$getDivMaster = $conoracle->prepare("SELECT 
								SUM(DIVIDEND_PAYMENT) AS DIV_AMT,
								SUM(AVERAGE_PAYMENT) AS  AVG_AMT,
								SUM(DIVIDEND_PAYMENT+AVERAGE_PAYMENT) AS SUMDIV
							FROM 
								mbdivavgtrnpost 
							WHERE 
								TRIM(MEMBER_NO) = :member_no
								AND DIVAVG_YEAR = :div_year GROUP BY DIVAVG_YEAR");
			$getDivMaster->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$rowDiv = $getDivMaster->fetch(PDO::FETCH_ASSOC);
			$getRateDiv = $conoracle->prepare("SELECT DIVIDEND_RATE,AVERAGE_RATE FROM mbdivavgrate WHERE divavg_year = :year");
			$getRateDiv->execute([':year' => $rowYear["DIV_YEAR"]]);
			$rowRateDiv = $getRateDiv->fetch(PDO::FETCH_ASSOC);
			$arrDividend["YEAR"] = $rowYear["DIV_YEAR"];
			$arrDividend["DIV_RATE"] = ($rowRateDiv["DIVIDEND_RATE"] * 100)." %";
			$arrDividend["AVG_RATE"] = ($rowRateDiv["AVERAGE_RATE"] * 100)." %";
			$arrDividend["DIV_AMT"] = number_format($rowDiv["DIV_AMT"],2);
			$arrDividend["AVG_AMT"] = number_format($rowDiv["AVG_AMT"],2);
			$arrDividend["SUM_AMT"] = number_format($rowDiv["SUMDIV"],2);
			$getMethpay = $conoracle->prepare("SELECT
												(CASE WHEN md.DIVAVG_CODE = 'CSH' THEN 'เงินสด'
														WHEN md.DIVAVG_CODE = 'TRS' THEN 'โอนซื้อหุ้น'
														WHEN md.DIVAVG_CODE = 'TRN' THEN 'โอนเข้าเงินฝาก'
														WHEN md.DIVAVG_CODE = 'CBT' THEN 'โอนเข้าธนาคาร'
												ELSE '' END) AS TYPE_DESC,
												md.TRANSPAY_AMT AS RECEIVE_AMT ,						
												md.DIVAVG_ACCID AS BANK_ACCOUNT,
												cmb.BANK_DESC as BANK_NAME,
												cmbb.BRANCH_NAME,
												md.DIVAVG_CODE
											FROM 
												mbdivavgtrnpost md LEFT JOIN cmucfbank cmb ON md.divavg_bank = cmb.bank_code	
												LEFT JOIN cmucfbankbranch cmbb ON md.divavg_branch = cmbb.branch_id and
												md.divavg_bank = cmbb.bank_code
											WHERE  
												md.divavg_code IN ('CSH','TRS','TRN','CBT') AND 
												TRIM(md.MEMBER_NO) = :member_no
												AND md.DIVAVG_YEAR= :div_year");
			$getMethpay->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			while($rowMethpay = $getMethpay->fetch(PDO::FETCH_ASSOC)){
				$arrayRecv = array();
				$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount($rowMethpay["BANK_ACCOUNT"],'xxx-xxxxxx-x');
				if($rowMethpay["DIVAVG_CODE"] == 'CBT'){
					$arrayRecv["RECEIVE_DESC"] = $rowMethpay["TYPE_DESC"].' '.$rowMethpay["BANK_NAME"].' '.$rowMethpay["BRANCH_NAME"];
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