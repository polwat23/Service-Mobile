<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanInfo')){
		$arrAllLoan = array();
		$arrayExecute = array();
		$arrayContractCheckGrp = array();  //เช็คยืนยันยอดเงินกู้
		
		$limit = $func->getConstant('limit_loancontract');
		$arrLimit['LIMIT_DURATION'] = $limit;
		$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		$date_now = date('Y-m-d');
		
		$arrayExecute["member_no"] =  $payload["ref_memno"];
		$arrayExecute["datebefore"] = $date_before;
		$arrayExecute["datenow"] = $date_now;
		
		if(isset($dataComing["contract_status"]) && $dataComing["contract_status"] != ""){
				$arrayExecute["contract_status"] = $dataComing["contract_status"];
		}
		
		$fetchContractTypeCheck = $conmysql->prepare("SELECT CONTRACT_NO FROM gcconstantcontractno WHERE IS_CLOSESTATUS ='1' AND member_no = :member_no ");
		$fetchContractTypeCheck->execute([':member_no' => $payload["ref_memno"]]);
		while($rowContractnoCheck = $fetchContractTypeCheck->fetch(PDO::FETCH_ASSOC)){
			$arrayContractCheckGrp[] = $rowContractnoCheck["CONTRACT_NO"];
		}
		
		$getSumAllContract = $conoracle->prepare("SELECT SUM(principal_balance) as SUM_LOANBALANCE FROM lccontmaster WHERE TRIM(member_no) = :member_no 
										   AND startcont_date BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') "."
										".(isset($dataComing["contract_status"]) && $dataComing["contract_status"] != "" ? "AND contract_status = :contract_status" : null)."");
		$getSumAllContract->execute($arrayExecute);
		$rowSumloanbalance = $getSumAllContract->fetch(PDO::FETCH_ASSOC);
		$arrayResult['SUM_LOANBALANCE'] = number_format($rowSumloanbalance["SUM_LOANBALANCE"],2);
		
		$getContract = $conoracle->prepare("SELECT lt.LOANTYPE_DESC AS LOAN_TYPE,ln.loancontract_no,ln.principal_balance as LOAN_BALANCE,
										ln.loanapprove_amt as APPROVE_AMT,ln.startcont_date,ln.period_payment, ln.period_installment as PERIOD,
										ln.LAST_PERIODPAY as LAST_PERIOD, ln.CONTRACT_STATUS,
										(SELECT max(operate_date) FROM lccontstatement WHERE loancontract_no = ln.loancontract_no) as LAST_OPERATE_DATE
										FROM lccontmaster ln LEFT JOIN LCCFLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
										WHERE TRIM(ln.member_no) = :member_no AND  ln.startcont_date BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD')"."
										".(isset($dataComing["contract_status"]) && $dataComing["contract_status"] != "" ? 
										"and ln.contract_status = :contract_status" : null)."
										".(count($arrayContractCheckGrp) > 0 ? ("AND ln.loancontract_no NOT IN('".implode("','",$arrayContractCheckGrp)."')") : null)." ");
		$getContract->execute($arrayExecute);
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
			}else{
				$arrContract["CONTRACT_STATUS"] = "สัญญาอื่นๆ";
				$arrContract["IS_CLOSE"] = true;
			}
			$arrContract["LOAN_BALANCE"] = number_format($rowContract["LOAN_BALANCE"],2);
			$arrContract["APPROVE_AMT"] = number_format($rowContract["APPROVE_AMT"],2);
			$arrContract["LAST_OPERATE_DATE"] = $lib->convertdate($rowContract["LAST_OPERATE_DATE"],'y-n-d');
			$arrContract["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowContract["LAST_OPERATE_DATE"],'D m Y');
			$arrContract["STARTCONT_DATE"] = $lib->convertdate($rowContract["STARTCONT_DATE"],'D m Y');
			$arrContract["PERIOD_PAYMENT"] = number_format($rowContract["PERIOD_PAYMENT"],2);
			$arrContract["PERIOD"] = $rowContract["LAST_PERIOD"].' / '.$rowContract["PERIOD"];
			$arrGroupContract['TYPE_LOAN'] = $rowContract["LOAN_TYPE"];
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
		$arrayStatusItem["VALUE"] = "-9";
		$arrayStatusItem["LABEL"] = "สัญญาอื่นๆ";
		$arrayLoanStatusList[] = $arrayStatusItem;
		$arrayStatusItem = [];
		$arrayStatusItem["VALUE"] = "-1";
		$arrayStatusItem["LABEL"] = "ปิดสัญญา";
		$arrayLoanStatusList[] = $arrayStatusItem;
		
		$arrayResult['DETAIL_LOAN'] = $arrAllLoan;
		$arrayResult['LIST_LOANSTATUS'] = $arrayLoanStatusList;
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