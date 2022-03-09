<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DividendInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrDivmaster = array();
		$limit_year = $func->getConstant('limit_dividend');
		$getYeardividend = $conoracle->prepare("SELECT * FROM (SELECT yr.DIV_YEAR AS DIV_YEAR FROM YRDIVMASTER yrm LEFT JOIN yrcfrate yr 
												ON yrm.DIV_YEAR = yr.DIV_YEAR WHERE yrm.MEMBER_NO = :member_no and yr.LOCKPROC_FLAG = '1' 
												GROUP BY yr.DIV_YEAR ORDER BY yr.DIV_YEAR DESC) where rownum <= :limit_year");
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
			$arrDividend["DIV_AMT"] = number_format($rowDiv["DIV_AMT"],2);
			$arrDividend["AVG_AMT"] = number_format($rowDiv["AVG_AMT"],2);
			$arrDividend["SUM_AMT"] = number_format($rowDiv["DIV_AMT"] + $rowDiv["AVG_AMT"],2);
			$getMethpay = $conoracle->prepare("SELECT
													yu.METHPAYTYPE_DESC as TYPE_DESC,
													ym.CBT_AMT AS RECEIVE_AMT ,						
													ym.BANK_ACCID AS BANK_ACCOUNT
												FROM 
													YRDIVMASTER ym,YRUCFMETHPAY yu
												WHERE 
													ym.MEMBER_NO = :member_no
													AND ym.DIV_YEAR= :div_year and yu.METHPAYTYPE_CODE = 'CBT'");
			$getMethpay->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			while($rowMethpay = $getMethpay->fetch(PDO::FETCH_ASSOC)){
				$arrayRecv = array();
				$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount_hidden($lib->formataccount($rowMethpay["BANK_ACCOUNT"],'xxx-xxxxxx-x'),'hhh-hhxxxx-h');
				$arrayRecv["RECEIVE_DESC"] = $rowMethpay["TYPE_DESC"];
				$arrayRecv["RECEIVE_AMT"] = number_format($rowMethpay["RECEIVE_AMT"],2);
				$arrDividend["RECEIVE_ACCOUNT"][] = $arrayRecv;
			}
			$getPaydiv = $conoracle->prepare("SELECT   
													W01_AMT AS W01,
													W02_AMT AS W02,
													(LONPRN_AMT + LONINT_AMT) AS LON,
													SDV_AMT AS SDV,
													SQT_AMT AS SQT,
													MRT_AMT AS MRT,
													DEP_AMT AS DEP
													FROM yrdivmaster
														WHERE MEMBER_NO = :member_no
														and DIV_YEAR = :div_year");
			$getPaydiv->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$arrayPayGroup = array();
			$sumPay = 0;
			
			$rowPaydiv = $getPaydiv->fetch(PDO::FETCH_ASSOC);
			foreach($rowPaydiv as $key => $value){
				if($value > 0){
					$fetchNameType = $conoracle->prepare("SELECT METHPAYTYPE_DESC as TYPE_DESC FROM yrucfmethpay WHERE METHPAYTYPE_CODE = :code");
					$fetchNameType->execute([':code' => $key]);
					$nametype = $fetchNameType->fetch(PDO::FETCH_ASSOC);

					$arrPay = array();
					$arrPay["TYPE_DESC"] = $nametype["TYPE_DESC"] ?? '';
					$arrPay["PAY_AMT"] = number_format($value,2);
					$sumPay += $value;
					$arrayPayGroup[] = $arrPay;
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