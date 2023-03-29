<?php
require_once('../autoload.php');

$conoracle = $con->connecttooldoracle();

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DividendInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrDivmaster = array();
		$limit_year = $func->getConstant('limit_dividend');
		$getYeardividend = $conoracle->prepare("SELECT * FROM (SELECT yr.DIV_YEAR AS DIV_YEAR FROM MBDIVMASTER yrm LEFT JOIN mbdivrate yr 
												ON yrm.DIV_YEAR = yr.DIV_YEAR WHERE yrm.MEMBER_NO = :member_no 
												GROUP BY yr.DIV_YEAR ORDER BY yr.DIV_YEAR DESC) where rownum <= :limit_year");
		$getYeardividend->execute([
			':member_no' => $member_no,
			':limit_year' => $limit_year
		]);
		while($rowYear = $getYeardividend->fetch(PDO::FETCH_ASSOC)){
			$arrDividend = array();
			$getDivMaster = $conoracle->prepare("SELECT dividend_amt as div_amt, average_amt as avg_amt FROM MBDIVMASTER WHERE member_no = :member_no and div_year = :div_year");
			$getDivMaster->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$rowDiv = $getDivMaster->fetch(PDO::FETCH_ASSOC);
			$arrDividend["YEAR"] = $rowYear["DIV_YEAR"];
			$arrDividend["DIV_AMT"] = number_format($rowDiv["DIV_AMT"],2);
			$arrDividend["AVG_AMT"] = number_format($rowDiv["AVG_AMT"],2);
			$arrDividend["SUM_AMT"] = number_format($rowDiv["DIV_AMT"] + $rowDiv["AVG_AMT"],2);
			$arrDividend["DIVPERCENT_RATE"] = number_format($rowDiv["DIV_AMT"]/($rowDiv["DIV_AMT"] + $rowDiv["AVG_AMT"]) * 100,2);
			$arrDividend["AVGPERCENT_RATE"] = number_format($rowDiv["AVG_AMT"]/($rowDiv["DIV_AMT"] + $rowDiv["AVG_AMT"]) * 100,2);
			
			
			$getMethpay = $conoracle->prepare("SELECT
													CUCF.DIVPAYTYPE_DESC AS TYPE_DESC,
													CM.BANK_DESC AS BANK,
													YM.DEPTACCOUNT_NO as RECEIVE_DESC,
													YM.ITEM_AMT AS RECEIVE_AMT ,						
													YM.BANK_ACCID AS BANK_ACCOUNT,
													YM.DEPTACCOUNT_NO ,
													YM.DIVPAYTYPE_CODE,
													YM.DESCRIPTION
												FROM 
													MBDIVMETHODPAYMENT YM LEFT JOIN MBDIVPAYTYPE CUCF ON
													YM.DIVPAYTYPE_CODE = CUCF.DIVPAYTYPE_CODE
													LEFT JOIN CMUCFBANK CM ON YM.BANK_CODE = CM.BANK_CODE
												WHERE
													YM.MEMBER_NO = :member_no
													AND YM.DIVPAYTYPE_CODE IN('CBT','CSH','DEP','SHR','D00')
													AND YM.DIV_YEAR = :div_year");
			$getMethpay->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			while($rowMethpay = $getMethpay->fetch(PDO::FETCH_ASSOC)){
				$arrayRecv = array();
				if($rowMethpay["DIVPAYTYPE_CODE"] == "CBT" || $rowMethpay["DIVPAYTYPE_CODE"] == "DEP"){
					if(isset($rowMethpay["BANK"])){
						$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount_hidden($lib->formataccount($rowMethpay["BANK_ACCOUNT"],'xxx-xxxxxx-x'),'xxx-xxxxxx-x');
					}else{
						$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount_hidden($lib->formataccount($rowMethpay["BANK_ACCOUNT"],$func->getConstant('dep_format')),$func->getConstant('hidden_dep'));
					}
				}else{
					if($rowMethpay["DIVPAYTYPE_CODE"] =='D00'){
						$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount_hidden($lib->formataccount(substr_replace(substr($rowMethpay["DEPTACCOUNT_NO"],3,13), "0", 2, 0),'xx-xxxxxxxx'),'xx-xxxxxxxxx'); 
					}	
				}                                                              
				$arrayRecv["RECEIVE_DESC"] = $rowMethpay["TYPE_DESC"];
				if(isset($rowMethpay["DEPTACCOUNT_NO"])){
					$arrayRecv["BANK"] = $rowMethpay["DESCRIPTION   "];
				}else{
					$arrayRecv["BANK"] = $rowMethpay["BANK"];
				}
				$arrayRecv["RECEIVE_AMT"] = number_format($rowMethpay["RECEIVE_AMT"],2);
				$arrDividend["RECEIVE_ACCOUNT"][] = $arrayRecv;
			}
			$getPaydiv = $conoracle->prepare("SELECT yucf.DIVPAYTYPE_DESC AS TYPE_DESC,ymp.item_amt as pay_amt
											FROM MBDIVMETHODPAYMENT ymp LEFT JOIN MBDIVPAYTYPE yucf ON ymp.DIVPAYTYPE_CODE = yucf.DIVPAYTYPE_CODE
											WHERE ymp.MEMBER_NO = :member_no and ymp.div_year = :div_year and ymp.DIVPAYTYPE_CODE NOT IN('CBT','CSH','DEP')");
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
