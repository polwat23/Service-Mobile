<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DividendInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrDivmaster = array();
		$limit_year = $func->getConstant('limit_dividend');
		$getYeardividend = $conoracle->prepare("SELECT * FROM (SELECT yrm.DIV_YEAR AS DIV_YEAR FROM MBDIVMASTER yrm 
												WHERE yrm.MEMBER_NO = :member_no
												GROUP BY yrm.DIV_YEAR ORDER BY yrm.DIV_YEAR DESC) where rownum <= :limit_year");
		$getYeardividend->execute([
			':member_no' => $member_no,
			':limit_year' => $limit_year
		]);
		while($rowYear = $getYeardividend->fetch(PDO::FETCH_ASSOC)){
			$arrDividend = array();
			$getDivMaster = $conoracle->prepare("SELECT dividend_amt as div_amt,average_amt as avg_amt FROM MBDIVMASTER WHERE member_no = :member_no and div_year = :div_year");
			$getDivMaster->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$rowDiv = $getDivMaster->fetch(PDO::FETCH_ASSOC);
			$getDivETC = $conoracle->prepare("SELECT SUM(MDS.ETC_PAYMENT) AS ETC_AMT FROM MBDIVSTATEMENT MDS LEFT JOIN MBDIVPAYTYPE MDP ON MDS.DIVPAYTYPE_CODE = MDP.DIVPAYTYPE_CODE
											WHERE MDS.DIV_YEAR = :year AND MDS.MEMBER_NO = :member_no AND MDP.SHOW_FLAG = 1 AND MDP.SIGN_FLAG = 1");
			$getDivETC->execute([
				':div_year' => $rowYear["DIV_YEAR"],
				':member_no' => $member_no
			]);
			$rowDivEtc = $getDivETC->fetch(PDO::FETCH_ASSOC);
			$arrDividend["YEAR"] = $rowYear["DIV_YEAR"];
			$arrDividend["DIV_AMT"] = number_format($rowDiv["DIV_AMT"],2);
			$arrDividend["AVG_AMT"] = number_format($rowDiv["AVG_AMT"],2);
			$arrDividend["ETC_AMT"] = number_format($rowDivEtc["ETC_AMT"],2);
			$arrDividend["SUM_AMT"] = number_format($rowDiv["DIV_AMT"] + $rowDiv["AVG_AMT"] + $rowDivEtc["ETC_AMT"],2);
			$getMethpay = $conoracle->prepare("SELECT MDP.DIVPAYTYPE_DESC AS TYPE_DESC,MDM.ITEM_AMT AS ITEM_AMT ,MDM.CUT_TYPE,
											TRIM( CASE WHEN CUT_TYPE = 1 THEN CUT_PERCENT * 100
											WHEN CUT_TYPE = 2 THEN CUT_AMOUNT
											ELSE 0 END ) AS CUT_AMOUNT ,
											(CASE
												WHEN MDP.DIVPAYTYPE_CODE IN ('SHR','ACC','LJD','LON','D00','D01','D02','D03') THEN ''
												ELSE CUB.BANK_DESC 
											END ) AS BANK ,
											(CASE WHEN MDP.DIVPAYTYPE_CODE IN ('SHR','ACC','LJD','LON') THEN ''
												ELSE 
											(CASE 
												WHEN MDP.DIVPAYTYPE_CODE IN ('CBT','CBS') THEN MDM.BANK_ACCID
												WHEN MDP.DIVPAYTYPE_CODE IN ('D00', 'D01', 'D02', 'D03') THEN MDM.DEPTACCOUNT_NO
											END ) END ) AS BANK_ACCOUNT 
											FROM MBDIVMETHODPAYMENT MDM LEFT JOIN MBDIVPAYTYPE MDP
											ON MDM.DIVPAYTYPE_CODE = MDP.DIVPAYTYPE_CODE LEFT JOIN CMUCFBANK CUB ON MDM.BANK_CODE = CUB.BANK_CODE
											WHERE MDM.MEMBER_NO = :member_no AND MDM.DIV_YEAR = :div_year");
			$getMethpay->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$sum_recv = 0;
			while($rowMethpay = $getMethpay->fetch(PDO::FETCH_ASSOC)){
				$arrayRecv = array();
				$sum_recv += $rowMethpay["ITEM_AMT"];
				$arrayRecv["CUT_TYPE"] = $rowMethpay["CUT_TYPE"];
				$arrayRecv["CUT_AMOUNT"] = $rowMethpay["CUT_AMOUNT"];
				$arrayRecv["RECEIVE_AMT"] = number_format($rowMethpay["ITEM_AMT"],2);
				if(isset($rowMethpay["BANK_DESC"])){
					$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount_hidden($lib->formataccount($rowMethpay["BANK_ACCOUNT"],'xxx-xxxxxx-x'),'hhh-hhxxxx-h');
				}else{
					$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount_hidden($lib->formataccount($rowMethpay["BANK_ACCOUNT"],$func->getConstant('dep_format')),$func->getConstant('hidden_dep'));
				}
				$arrayRecv["RECEIVE_DESC"] = $rowMethpay["TYPE_DESC"];
				$arrayRecv["BANK"] = $rowMethpay["BANK"];
				$arrDividend["RECEIVE_ACCOUNT"][] = $arrayRecv;
			}
			$getPaydiv = $conoracle->prepare("SELECT MDP.DIVPAYTYPE_DESC as TYPE_DESC,MDS.ETC_PAYMENT * -1 as PAY_AMT
											FROM MBDIVSTATEMENT MDS LEFT JOIN MBDIVPAYTYPE MDP ON MDS.DIVPAYTYPE_CODE = MDP.DIVPAYTYPE_CODE
											WHERE MDS.MEMBER_NO = :member_no AND MDS.DIV_YEAR = :div_year AND MDP.SHOW_FLAG = 1 AND MDP.SIGN_FLAG =  -1 ");
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
			$arrDividend["RECEIVE_AMT"] = number_format($sum_recv - $sumPay,2);
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