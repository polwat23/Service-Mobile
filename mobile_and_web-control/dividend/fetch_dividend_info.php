<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DividendInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrDivmaster = array();
		$limit_year = $func->getConstant('limit_dividend');
		$getYeardividend = $conoracle->prepare("SELECT * FROM (SELECT YR.DIV_YEAR AS DIV_YEAR,YF.DIVPERCENT_RATE,YF.AVGPERCENT_RATE 
												FROM YRDIVMASTER YR LEFT JOIN YRCFRATE YF ON YR.DIV_YEAR = YF.DIV_YEAR
												WHERE YR.MEMBER_NO = :member_no and YF.WEBSHOW_FLAG = '1'
												GROUP BY YR.DIV_YEAR,YF.DIVPERCENT_RATE,YF.AVGPERCENT_RATE  
												ORDER BY YR.DIV_YEAR DESC) where rownum <= :limit_year");
		$getYeardividend->execute([
			':member_no' => $member_no,
			':limit_year' => $limit_year
		]);
		while($rowYear = $getYeardividend->fetch(PDO::FETCH_ASSOC)){
			$checkBlackList = $conoracle->prepare("SELECT COUNT(*) AS C_MEMB FROM YRBLACKLIST WHERE div_year = :div_year AND MEMBER_NO = :member_no");
			$checkBlackList->execute([
				':div_year' => $rowYear["DIV_YEAR"],
				':member_no' => $member_no
			]);
			$rowBlackList = $checkBlackList->fetch(PDO::FETCH_ASSOC);
			if($rowBlackList["C_MEMB"] == 0){
				$arrDividend = array();
				$getDivMaster = $conoracle->prepare("SELECT DIV_AMT,AVG_AMT FROM YRDIVMASTER WHERE MEMBER_NO = :member_no AND DIV_YEAR = :div_year");
				$getDivMaster->execute([
					':member_no' => $member_no,
					':div_year' => $rowYear["DIV_YEAR"]
				]);
				$rowDiv = $getDivMaster->fetch(PDO::FETCH_ASSOC);
				$arrDividend["YEAR"] = $rowYear["DIV_YEAR"];
				$arrDividend["DIV_PERCENT"] = $rowYear["DIVPERCENT_RATE"].' %';
				$arrDividend["AVG_PERCENT"] = $rowYear["AVGPERCENT_RATE"].' %';
				$arrDividend["DIV_AMT"] = number_format($rowDiv["DIV_AMT"],2);
				$arrDividend["AVG_AMT"] = number_format($rowDiv["AVG_AMT"],2);
				$arrDividend["SUM_AMT"] = number_format($rowDiv["DIV_AMT"] + $rowDiv["AVG_AMT"],2);				
				$getMethpay = $conoracle->prepare("SELECT 
														D.METHPAYTYPE_DESC AS TYPE_DESC,
														''  AS BANK,
														(select (cc.div_amt + cc.avg_amt - (select 
														nvl(sum(pay_amt),0)
														from yrreqmethpay a
														left join yrreqmethpaydet b on a.methreq_docno = b.methreq_docno
														where a.member_no = cc.member_No and a.DIV_YEAR = cc.DIV_YEAR
														and b.paytype_code = 'VAL'  and a.methreq_status in (8,1))) as avg_amt 
														from yrdivmaster cc
														where cc.member_no = :member_no and cc.DIV_YEAR = :div_year
														) AS RECEIVE_AMT ,						
														C.EXPENSE_ACCID AS BANK_ACCOUNT,
														DECODE(C.METHPAYTYPE_CODE,NULL,'CHQ',C.METHPAYTYPE_CODE) AS METHPAYTYPE_CODE
													FROM YRBGMASTER A 
													LEFT JOIN YRREQMETHPAY B ON B.MEMBER_NO = A.MEMBER_NO AND A.DIV_YEAR = B.DIV_YEAR AND B.METHREQ_STATUS IN (8,1)
													LEFT JOIN YRREQMETHPAYDET C ON B.METHREQ_DOCNO = C.METHREQ_DOCNO AND C.PAYTYPE_CODE = 'ALL' 
													LEFT JOIN YRUCFMETHPAY D ON DECODE(C.METHPAYTYPE_CODE,NULL,'CHQ',C.METHPAYTYPE_CODE) = D.METHPAYTYPE_CODE
													WHERE A.MEMBER_NO = :member_no AND A.DIV_YEAR = :div_year");
				
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
					if($rowMethpay["RECEIVE_AMT"] != 0){
						$arrDividend["RECEIVE_ACCOUNT"][] = $arrayRecv;
					}
				}
				$getPaydiv = $conoracle->prepare("SELECT 
														C.METHPAYTYPE_DESC AS TYPE_DESC,
														B.PAY_AMT AS PAY_AMT
													FROM YRREQMETHPAY A 
													LEFT JOIN YRREQMETHPAYDET B ON A.METHREQ_DOCNO = B.METHREQ_DOCNO
													LEFT JOIN YRUCFMETHPAY C ON B.METHPAYTYPE_CODE = C.METHPAYTYPE_CODE
													WHERE A.MEMBER_NO = :member_no AND A. DIV_YEAR = :div_year
													AND B.PAYTYPE_CODE = 'VAL' AND A.METHREQ_STATUS IN (8,1)
													ORDER BY B.PAYSEQ_NO");
				
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