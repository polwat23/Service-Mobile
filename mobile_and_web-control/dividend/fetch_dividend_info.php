<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DividendInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrDivmaster = array();
		$limit_year = $func->getConstant('limit_dividend');
		$getYeardividend = $conoracle->prepare("SELECT * FROM (SELECT yr.DIV_YEAR AS DIV_YEAR,yr.DIVPERCENT_RATE,yr.AVGPERCENT_RATE
												FROM YRDIVMASTER yrm LEFT JOIN yrcfrate yr 
												ON yrm.DIV_YEAR = yr.DIV_YEAR WHERE yrm.MEMBER_NO = :member_no and yr.WEBSHOW_FLAG = '1' 
												GROUP BY yr.DIV_YEAR,yr.DIVPERCENT_RATE,yr.AVGPERCENT_RATE 
												ORDER BY yr.DIV_YEAR DESC) where rownum <= :limit_year");
		$getYeardividend->execute([
			':member_no' => $member_no,
			':limit_year' => $limit_year
		]);
		while($rowYear = $getYeardividend->fetch(PDO::FETCH_ASSOC)){
			$arrDividend = array();
			$getDivMaster = $conoracle->prepare("SELECT div_amt,avg_amt FROM yrdivmaster WHERE member_no = :member_no and div_year = :div_year");
			$getDivMaster->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$rowDiv = $getDivMaster->fetch(PDO::FETCH_ASSOC);
			$arrDividend["YEAR"] = $rowYear["DIV_YEAR"];
			$arrDividend["AVG_RATE"] = ($rowYear["AVGPERCENT_RATE"] * 100).'%';
			$arrDividend["DIV_RATE"] = ($rowYear["DIVPERCENT_RATE"] * 100).'%';
			$arrDividend["DIV_AMT"] = number_format($rowDiv["DIV_AMT"],2);
			$arrDividend["AVG_AMT"] = number_format($rowDiv["AVG_AMT"],2);
			$arrDividend["SUM_AMT"] = number_format($rowDiv["DIV_AMT"] + $rowDiv["AVG_AMT"],2);
			$getMethpay = $conoracle->prepare("SELECT
													CUCF.MONEYTYPE_DESC AS TYPE_DESC,
													CM.BANK_DESC AS BANK,
													YM.EXPENSE_AMT AS RECEIVE_AMT ,						
													YM.EXPENSE_ACCID AS BANK_ACCOUNT,
													YM.METHPAYTYPE_CODE
												FROM 
													YRDIVMETHPAY YM LEFT JOIN CMUCFMONEYTYPE CUCF ON
													YM.MONEYTYPE_CODE = CUCF.MONEYTYPE_CODE
													LEFT JOIN CMUCFBANK CM ON YM.EXPENSE_BANK = CM.BANK_CODE
												WHERE
													YM.MEMBER_NO = :member_no
													AND YM.METHPAYTYPE_CODE IN('CBT','CSH','DEP')
													AND YM.DIV_YEAR = :div_year");
			$getMethpay->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			while($rowMethpay = $getMethpay->fetch(PDO::FETCH_ASSOC)){
				$arrayRecv = array();
				if($rowMethpay["METHPAYTYPE_CODE"] == "CBT" || $rowMethpay["METHPAYTYPE_CODE"] == "DEP"){
					if($rowMethpay["METHPAYTYPE_CODE"] == "CBT"){
						$arrayRecv["BANK"] = $rowMethpay["BANK"];
						$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount($rowMethpay["BANK_ACCOUNT"],'xxx-xxxxxx-x');
					}else{
						$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount($rowMethpay["BANK_ACCOUNT"],$func->getConstant('dep_format'));
					}
				}
				$arrayRecv["RECEIVE_DESC"] = $rowMethpay["TYPE_DESC"];
				$arrayRecv["RECEIVE_AMT"] = number_format($rowMethpay["RECEIVE_AMT"],2);
				$arrDividend["RECEIVE_ACCOUNT"][] = $arrayRecv;
			}
			
			$getPaydiv = $conoracle->prepare("SELECT yucf.methpaytype_desc AS TYPE_DESC,ymp.expense_amt as pay_amt
											FROM yrdivmethpay ymp LEFT JOIN yrucfmethpay yucf ON ymp.methpaytype_code = yucf.methpaytype_code
											WHERE ymp.MEMBER_NO = :member_no and ymp.div_year = :div_year and ymp.methpaytype_code NOT IN('CBT','CSH','DEP')");
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
			//กรณีไม่พบรายการหัก
			if(sizeof($arrayPayGroup) == 0){
				$getPaydivReq = $conoracle->prepare("SELECT yucf.methpaytype_desc AS TYPE_DESC,ymd.pay_amt as pay_amt
												FROM yrreqmethpay ymp
												LEFT JOIN yrreqmethpaydet ymd ON ymp.METHREQ_DOCNO = ymd.METHREQ_DOCNO
												LEFT JOIN yrucfmethpay yucf ON yucf.methpaytype_code = ymd.methpaytype_code
												WHERE ymp.MEMBER_NO = :member_no and ymp.div_year = :div_year and ymd.methpaytype_code NOT IN('CBT','CSH','DEP')");
				$getPaydivReq->execute([
					':member_no' => $member_no,
					':div_year' => $rowYear["DIV_YEAR"]
				]);
				$arrayPayGroup = array();
				$sumPay = 0;
				while($rowPayReq = $getPaydivReq->fetch(PDO::FETCH_ASSOC)){
					$arrPay = array();
					$arrPay["TYPE_DESC"] = $rowPayReq["TYPE_DESC"];
					$arrPay["PAY_AMT"] = number_format($rowPayReq["PAY_AMT"],2);
					$sumPay += $rowPayReq["PAY_AMT"];
					$arrayPayGroup[] = $arrPay;
				}
			}
			//กรณีไม่พบรายการจ่าย
			if(sizeof($arrDividend["RECEIVE_ACCOUNT"]) == 0){
				$getMethpayreq = $conoracle->prepare("SELECT CUCF.MONEYTYPE_DESC AS TYPE_DESC,
												CM.BANK_DESC AS BANK,
												ymd.pay_amt AS RECEIVE_AMT ,						
												ymd.EXPENSE_ACCID AS BANK_ACCOUNT,
												ymd.METHPAYTYPE_CODE
												FROM yrreqmethpay ym
												LEFT JOIN yrreqmethpaydet ymd ON ym.METHREQ_DOCNO = ymd.METHREQ_DOCNO
												LEFT JOIN CMUCFMONEYTYPE CUCF ON CUCF.MONEYTYPE_CODE = ymd.MONEYTYPE_CODE
												LEFT JOIN CMUCFBANK CM ON CM.BANK_CODE = ymd.expense_bank
												WHERE ym.MEMBER_NO = :member_no and ym.div_year = :div_year and ymd.methpaytype_code IN('CBT','CSH','DEP')");
				$getMethpayreq->execute([
					':member_no' => $member_no,
					':div_year' => $rowYear["DIV_YEAR"]
				]);
				while($rowMethpayreq = $getMethpayreq->fetch(PDO::FETCH_ASSOC)){
					$arrayRecv = array();
					if($rowMethpayreq["METHPAYTYPE_CODE"] == "CBT" || $rowMethpayreq["METHPAYTYPE_CODE"] == "DEP"){
						if($rowMethpayreq["METHPAYTYPE_CODE"] == "CBT"){
							$arrayRecv["BANK"] = $rowMethpayreq["BANK"];
							$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount($rowMethpayreq["BANK_ACCOUNT"],'xxx-xxxxxx-x');
						}else{
							$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount($rowMethpayreq["BANK_ACCOUNT"],$func->getConstant('dep_format'));
						}
					}
					$arrayRecv["RECEIVE_DESC"] = $rowMethpayreq["TYPE_DESC"];
					
					$recv_amt = ($rowDiv["DIV_AMT"] + $rowDiv["AVG_AMT"]) - $sumPay;
					$arrayRecv["RECEIVE_AMT"] = number_format($recv_amt,2);

					$arrDividend["RECEIVE_ACCOUNT"][] = $arrayRecv;
				}
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