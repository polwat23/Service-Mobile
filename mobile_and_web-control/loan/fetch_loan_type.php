<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanInfo')){
		$arrAllLoan = array();
		$arrayContractCheckGrp = array();  //เช็คยืนยันยอดเงินกู้
		
		$limit = $func->getConstant('limit_loancontract');
		$arrLimit['LIMIT_DURATION'] = $limit;
		$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		$date_now = date('Y-m-d');
		$Contractno = null;
		$member_no =  $payload["ref_memno"];
		$start_date = $date_before;
		$end_date = $date_now;
		
		$fetchContractTypeCheck = $conmysql->prepare("SELECT balance_status FROM gcconstantbalanceconfirm WHERE member_no = :member_no");
		$fetchContractTypeCheck->execute([':member_no' => $payload["ref_memno"]]);
		$rowContractnoCheck = $fetchContractTypeCheck->fetch(PDO::FETCH_ASSOC);
		$Contractno  = $rowContractnoCheck["balance_status"] || "0" ;
		
		if($Contractno == "0"){
			if(isset($dataComing["contract_status"]) && $dataComing["contract_status"] != ""){
				if($dataComing["contract_status"] == "-1"){
					$consign_date = "AND ln.CONTSIGN_DATE BETWEEN TO_DATE('".$start_date."','YYYY-MM-DD') and TO_DATE('".$end_date."','YYYY-MM-DD') ";	
					$contract_status = "AND contract_status =  '".$dataComing["contract_status"]."' ";
				}else if($dataComing["contract_status"] == "1"){					
					$contract_status = "AND contract_status =  '".$dataComing["contract_status"]."' ";
				}
			}else{
				$all_status = "and ln.contract_status =  1  OR (ln.member_no = '".$member_no."' AND  ln.contract_status = -1  AND ln.CONTSIGN_DATE BETWEEN TO_DATE('".$start_date."','YYYY-MM-DD') and TO_DATE('".$end_date."','YYYY-MM-DD'))";
			}
			$getSumAllContract = $conoracle->prepare("SELECT SUM(principal_balance) as SUM_LOANBALANCE FROM lccontmaster WHERE TRIM(member_no) = :member_no ".$contract_status."");
			$getSumAllContract->execute([':member_no' => $payload["ref_memno"]]);
			$rowSumloanbalance = $getSumAllContract->fetch(PDO::FETCH_ASSOC);
			$arrayResult['SUM_LOANBALANCE'] = number_format($rowSumloanbalance["SUM_LOANBALANCE"],2);
		

			$getContract = $conoracle->prepare("SELECT lt.LOANTYPE_DESC AS LOAN_TYPE,ln.loancontract_no,ln.principal_balance as LOAN_BALANCE,ln.loantype_code,
											ln.loanapprove_amt as APPROVE_AMT,ln.CONTSIGN_DATE,ln.period_payment, ln.period_installment as PERIOD,
											ln.LAST_PERIODPAY as LAST_PERIOD, ln.CONTRACT_STATUS,FT_GETCONTINTRATE(ln.branch_id,ln.loancontract_no,sysdate) as INT_CONTINTRATE, TO_CHAR(ln.EXPIRECONT_DATE, 'MON/ YYYY', 'NLS_CALENDAR=''THAI BUDDHA'' NLS_DATE_LANGUAGE=THAI') as EXPIRECONT_DATE,
											(SELECT max(operate_date) FROM lccontstatement WHERE loancontract_no = ln.loancontract_no) as LAST_OPERATE_DATE
											FROM lccontmaster ln LEFT JOIN LCCFLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
											WHERE TRIM(ln.member_no) = :member_no  
											".$consign_date."".$contract_status." ".$all_status."");
			$getContract->execute([':member_no' => $member_no]);
			while($rowContract = $getContract->fetch(PDO::FETCH_ASSOC)){
				$arrGroupContract = array();
				$contract_no = preg_replace('/\//','',$rowContract["LOANCONTRACT_NO"]);
				$arrContract = array();
				$arrContract["CONTRACT_NO"] = $contract_no;
				if($rowContract["CONTRACT_STATUS"] == "1"){
					$arrContract["CONTRACT_STATUS"] = "สัญญาปกติ";
					$arrContract["IS_CLOSE"] = false;
				}else if($rowContract["CONTRACT_STATUS"] == "-1"){
					$arrContract["CONTRACT_STATUS"] = "ปิดสัญญา";
					$arrContract["IS_CLOSE"] = true;
				}
				
				$arrContract["LOAN_BALANCE"] = number_format($rowContract["LOAN_BALANCE"],2);
				$arrContract["APPROVE_AMT"] = number_format($rowContract["APPROVE_AMT"],2);
				$arrContract["LAST_OPERATE_DATE"] = $lib->convertdate($rowContract["LAST_OPERATE_DATE"],'y-n-d');
				$arrContract["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowContract["LAST_OPERATE_DATE"],'D m Y');
				$arrContract["STARTCONT_DATE"] = $lib->convertdate($rowContract["CONTSIGN_DATE"],'D m Y');
				$arrContract["PERIOD_PAYMENT"] = number_format($rowContract["PERIOD_PAYMENT"],2);
				$arrContract["INT_CONTINTRATE"] = number_format($rowContract["INT_CONTINTRATE"],2) . " %";
				$arrContract["PERIOD"] = $rowContract["LAST_PERIOD"].' / '.$rowContract["PERIOD"];
				$arrContract["CONTRACT_PDF"] =  false;
				$arrGroupContract['TYPE_LOAN'] = $rowContract["LOAN_TYPE"];
			
				$fetchNotify= $conmysql->prepare("SELECT is_notify FROM gcconstanttypeloan WHERE loantype_code = :loantype_code");
				$fetchNotify->execute([':loantype_code' => $rowContract["LOANTYPE_CODE"]]);
				$rowContNotify= $fetchNotify->fetch(PDO::FETCH_ASSOC);
				
				$arrContract["PERIOD_ALERT"] = $rowContract["PERIOD"] - $rowContract["LAST_PERIOD"];
				if($arrContract["PERIOD_ALERT"] <= "3" &&  $arrContract["PERIOD_ALERT"] > "0" && $rowContNotify["is_notify"] == "1"){
					$arrContract["ALERT_STATUS"] = "เงินกู้สัญญานี้จะครบกำหนดสัญญา  เดือน  ".$rowContract["EXPIRECONT_DATE"];
				}
				
				if(array_search($rowContract["LOAN_TYPE"],array_column($arrAllLoan,'TYPE_LOAN')) === False){
					($arrGroupContract['CONTRACT'])[] = $arrContract;
					$arrAllLoan[] = $arrGroupContract;
				}else{
					($arrAllLoan[array_search($rowContract["LOAN_TYPE"],array_column($arrAllLoan,'TYPE_LOAN'))]["CONTRACT"])[] = $arrContract;
				}
			}
			
			$arrayLoanStatusList = [];
			$arrayStatusItem = [];
			$arrayStatusItem["VALUE"] = "1";
			$arrayStatusItem["LABEL"] = "สัญญาปกติ";
			$arrayLoanStatusList[] = $arrayStatusItem;
			$arrayStatusItem = [];
			$arrayStatusItem["VALUE"] = "-1";
			$arrayStatusItem["LABEL"] = "ปิดสัญญา";
			$arrayLoanStatusList[] = $arrayStatusItem;
			
			
			$arrayResult['$date_before'] = $contract_status ;
			$arrayResult['DETAIL_LOAN'] = $arrAllLoan;
			$arrayResult['LIST_LOANSTATUS'] = $arrayLoanStatusList;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0114";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
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