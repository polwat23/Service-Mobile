<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DividendInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrDivmaster = array();
		$limit_year = $func->getConstant('limit_dividend');
		$getYeardividend = $conmssql->prepare("SELECT TOP ".$limit_year." yr.DIV_YEAR AS DIV_YEAR FROM YRDIVMASTER yrm LEFT JOIN yrcfrate yr 
												ON yrm.DIV_YEAR = yr.DIV_YEAR WHERE yrm.MEMBER_NO = :member_no and yr.LOCKPROC_FLAG = '1' 
												GROUP BY yr.DIV_YEAR ORDER BY yr.DIV_YEAR DESC");
		$getYeardividend->execute([
			':member_no' => $member_no
		]);
		while($rowYear = $getYeardividend->fetch(PDO::FETCH_ASSOC)){
			$arrDividend = array();
			$getDivMaster = $conmssql->prepare("SELECT DIV_AMT,AVG_AMT FROM yrdivmaster WHERE member_no = :member_no and div_year = :div_year");
			$getDivMaster->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$rowDiv = $getDivMaster->fetch(PDO::FETCH_ASSOC);
			$arrDividend["YEAR"] = $rowYear["DIV_YEAR"]." ".$member_no;
			$arrDividend["DIV_AMT"] = number_format($rowDiv["DIV_AMT"],2);
			$arrDividend["AVG_AMT"] = number_format($rowDiv["AVG_AMT"],2);
			$arrDividend["SUM_AMT"] = number_format($rowDiv["DIV_AMT"] + $rowDiv["AVG_AMT"],2);
			
			$getPaydiv = $conmssql->prepare("SELECT yucf.methpaytype_desc AS TYPE_DESC,ymp.PAY_AMT as PAY_AMT, ymp.bizzaccount_no as BIZZACCOUNT_NO
											FROM yrdivmethpay ymp LEFT JOIN yrucfmethpay yucf ON ymp.methpaytype_code = yucf.methpaytype_code
											WHERE ymp.MEMBER_NO = :member_no and ymp.div_year = :div_year and ymp.methpaytype_code NOT IN('NXT','CBT','CSH','DEP')");
			$getPaydiv->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$arrayPayGroup = array();
			$sumPay = 0;
			while($rowPay = $getPaydiv->fetch(PDO::FETCH_ASSOC)){
				$arrPay = array();
				$arrPay["TYPE_DESC"] = $rowPay["TYPE_DESC"];
				$arrPay["BIZZACCOUNT_NO"] = $rowPay["BIZZACCOUNT_NO"];
				$arrPay["PAY_AMT"] = number_format($rowPay["PAY_AMT"],2);
				$sumPay += $rowPay["PAY_AMT"];
				$arrayPayGroup[] = $arrPay;
			}
			
			$receiveMinus = ($rowDiv["DIV_AMT"] + $rowDiv["AVG_AMT"]) - $sumPay;
			if($receiveMinus < 0){
				$arrayRecv = array();
				$arrayRecv["RECEIVE_DESC"] = 'รับสุทธิ';
				$arrayRecv["BANK"] = '*** กรณียอดหักไม่พอจ่าย กรุณามาชำระได้ที่สหกรณ์ฯหรือโอนเข้าธนาคารกรุงไทย เลขบัญชี 4131008564 สาขาหนองคาย ***';
				$arrayRecv["RECEIVE_AMT"] = number_format($receiveMinus,2);
				$arrDividend["RECEIVE_ACCOUNT"][] = $arrayRecv;
			}else{
				$getMethpay = $conmssql->prepare("SELECT
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
														AND YM.METHPAYTYPE_CODE IN('NXT','CBT','CSH','DEP')
														AND YM.DIV_YEAR = :div_year");
				$getMethpay->execute([
					':member_no' => $member_no,
					':div_year' => $rowYear["DIV_YEAR"]
				]);
				while($rowMethpay = $getMethpay->fetch(PDO::FETCH_ASSOC)){
					$arrayRecv = array();
					if($rowMethpay["METHPAYTYPE_CODE"] == "CBT" || $rowMethpay["METHPAYTYPE_CODE"] == "DEP"){
						if(isset($rowMethpay["BANK"])){
							$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount_hidden($lib->formataccount($rowMethpay["BANK_ACCOUNT"],'xxx-xxxxxx-x'),'hhh-hhxxxx-h');
						}else{
							$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount_hidden($lib->formataccount($rowMethpay["BANK_ACCOUNT"],$func->getConstant('dep_format')),$func->getConstant('hidden_dep'));
						}
					}
					$arrayRecv["RECEIVE_DESC"] = $rowMethpay["TYPE_DESC"];
					$arrayRecv["BANK"] = $rowMethpay["BANK"];
					$arrayRecv["RECEIVE_AMT"] = number_format($rowMethpay["RECEIVE_AMT"],2);
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