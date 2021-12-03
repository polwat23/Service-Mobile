<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Moratorium')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGrp = array();
		
		$checkLoanMoratorium = $conmssql->prepare("SELECT lt.LOANTYPE_DESC AS LOAN_TYPE,RTRIM(ln.LOANCONTRACT_NO) as LOANCONTRACT_NO,ln.principal_balance as LOAN_BALANCE,lt.LOANTYPE_CODE,LT.LOANGROUP_CODE,
											ln.loanapprove_amt as APPROVE_AMT,ln.STARTCONT_DATE,ln.PERIOD_PAYMENT,ln.period_payamt as PERIOD,
											ln.LAST_PERIODPAY as LAST_PERIOD,
											(SELECT max(operate_date) FROM lncontstatement WHERE loancontract_no = ln.loancontract_no) as LAST_OPERATE_DATE
											FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
											WHERE ln.member_no = :member_no and ln.contract_status > 0 and ln.contract_status <> 8");
		$checkLoanMoratorium->execute([':member_no' => $member_no]);
		while($rowLoanType = $checkLoanMoratorium->fetch(PDO::FETCH_ASSOC)){
			/*$arrLoanType = array();
			$arrLoanType["COOP_ID"] = $config["COOP_ID"];
			$arrLoanType["LOANTYPE_DESC"] = $rowLoanType["LOAN_TYPE"];
			$arrLoanType["LOANGROUP_CODE"] = $rowLoanType["LOANGROUP_CODE"];
			$arrLoanType["LOANCONTRACT_NO"] = $rowLoanType["LOANCONTRACT_NO"];
			$arrLoanType["LOANCONTRACT_REFNO"] = $rowLoanType["LOANCONTRACT_NO"];*/
			
			$arrGrp[$rowLoanType["LOANGROUP_CODE"]]["LOAN_BALANCE"] += $rowLoanType["LOAN_BALANCE"];
			
			
			$checkCanMora = $conmysql->prepare("SELECT is_moratorium FROM gcmoratorium WHERE LOANGROUP_CODE = :loangroup_code and member_no = :member_no and is_moratorium <> '0'");
			$checkCanMora->execute([
				':loangroup_code' =>  $rowLoanType["LOANGROUP_CODE"],
				':member_no' =>  $payload["member_no"]
			]);
			$rowCheck = $checkCanMora->fetch(PDO::FETCH_ASSOC);
			if($rowCheck["is_moratorium"] == '8'){
				$arrGrp[$rowLoanType["LOANGROUP_CODE"]]["IS_CANMORATORIUM"] = false;
				$arrGrp[$rowLoanType["LOANGROUP_CODE"]]["MORATORIUM_REMARK"] = "อยู่ในระหว่างรอเจ้าหน้าที่ยืนยันข้อมูล";
			}else if($rowCheck["is_moratorium"] == '1'){
				$arrGrp[$rowLoanType["LOANGROUP_CODE"]]["IS_CANMORATORIUM"] = false;
				$arrGrp[$rowLoanType["LOANGROUP_CODE"]]["MORATORIUM_REMARK"] = "อยู่ในระหว่างการพักชำระหนี้";
			}else{
				$arrGrp[$rowLoanType["LOANGROUP_CODE"]]["IS_CANMORATORIUM"] = true;
			}
			/*$fetchMoratorium = $conmysql->prepare("select rm.MORATORIUM_DOCNO, rm.LOANCONTRACT_NO, rm.REQUEST_DATE, rm.REQUEST_STATUS, 
																	rm.ENTRY_ID, rm.ENTRY_DATE, rm.CANCEL_ID, rm.CANCEL_DATE, rm.CANCEL_PRINCIPAL_BALANCE,rm.CANCEL_COOP,rm.CANCEL_DOCNO,cm.LOANTYPE_CODE,TO_CHAR(rm.ENTRY_DATE,'YYYY-MM-DD') as ENTRY_DATE_FORMAT
																	from LNREQMORATORIUM rm
																	join LNCONTMASTER cm ON cm.LOANCONTRACT_NO = rm.LOANCONTRACT_NO
																	where rm.member_no = :member_no AND rm.LOANCONTRACT_NO = :loancontract_no AND rm.REQUEST_STATUS = '1'
																	AND rm.ENTRY_DATE >= to_date('2021-06-01','YYYY-MM-DD')");
			$fetchMoratorium->execute([
				':member_no' => $member_no,
				':loancontract_no' => $rowLoanType["LOANCONTRACT_NO"]
			]);
			while($rowMoratorium = $fetchMoratorium->fetch(PDO::FETCH_ASSOC)){
				$arrMoratorium = array();
				$arrMoratorium["IS_SHOWREQ"] = true;
				if((int)date_create($rowQueue["ENTRY_DATE_FORMAT"])->format("Ymd") <= (int)date_create("2021-03-26")->format("Ymd")){
					$arrMoratorium["IS_CANCANCELREQ"] = false;
				}else if($rowMoratorium["LOANTYPE_CODE"] == '23' || $rowMoratorium["LOANTYPE_CODE"] == '24' || $rowMoratorium["LOANTYPE_CODE"] == '25' || $rowMoratorium["LOANTYPE_CODE"] == '26'){
					$arrMoratorium["IS_CANCANCELREQ"] = false;
				}else{
					$arrMoratorium["IS_CANCANCELREQ"] = true;
				}
				$arrMoratorium["IS_CANCANCELREQ"] = true;
				$arrMoratorium["MORATORIUM_DOCNO"] = $rowMoratorium["MORATORIUM_DOCNO"];
				$arrMoratorium["REQUEST_DATE"] = $lib->convertdate($rowMoratorium["ENTRY_DATE"] , 'D m Y', true);
				$arrLoanType["REQ_MORATORIUM"] = $arrMoratorium;
			}*/
			
			//$arrGrp[$rowLoanType["LOANGROUP_CODE"]]["CONTRACT"][] = $arrLoanType;
		}
		$arrayResult['LOAN_MORATORIUM'] = $arrGrp;
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