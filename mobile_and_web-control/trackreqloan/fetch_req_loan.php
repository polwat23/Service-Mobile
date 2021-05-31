<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestTrack')){
		$arrGrpReq = array();
		if(isset($dataComing["req_status"]) && $dataComing["req_status"] != ""){
			$fetchReqLoan = $conmysql->prepare("SELECT rl.reqloan_doc,rl.loantype_code,rl.request_amt,rl.period_payment,rl.period,req_status,rl.loanpermit_amt,
											rl.diff_old_contract,rl.receive_net,rl.bookbank_img,rl.bookcoop_img,rl.salary_img,rl.citizen_img,rl.remark,rl.approve_date,rl.contractdoc_url,
											rl.int_rate_at_req,rl.salary_at_req,rl.request_date,rl.deptaccount_no_bank,rl.bank_desc,rl.deptaccount_no_coop,rl.objective,pay_date,ep.extra_credit_name as extra_credit_project
											FROM gcreqloan  rl
											LEFT JOIN gcconstantextracreditproject ep ON rl.extra_credit_project = ep.id_extra_credit
											WHERE rl.member_no = :member_no and rl.req_status = :req_status ORDER BY rl.update_date DESC");
			$fetchReqLoan->execute([
				':member_no' => $payload["member_no"],
				':req_status' => $dataComing["req_status"]
			]);
			while($rowReqLoan = $fetchReqLoan->fetch(PDO::FETCH_ASSOC)){
				$getLoanType = $conmssql->prepare("SELECT LOANTYPE_DESC FROM lnloantype WHERE loantype_code = :loantype_code");
				$getLoanType->execute([':loantype_code' => $rowReqLoan["loantype_code"]]);
				$rowLoan = $getLoanType->fetch(PDO::FETCH_ASSOC);
				$arrayReq = array();
				$arrayReq["LOANTYPE_DESC"] = $rowLoan["LOANTYPE_DESC"];
				$arrayReq["REQLOAN_DOC"] = $rowReqLoan["reqloan_doc"];
				$arrayReq["LOANTYPE_CODE"] = $rowReqLoan["loantype_code"];
				$arrayReq["REQUEST_AMT"] = $rowReqLoan["request_amt"];
				$arrayReq["PERIOD_PAYMENT"] = $rowReqLoan["period_payment"];
				$arrayReq["PERIOD"] = $rowReqLoan["period"];
				$arrayReq["REQ_STATUS"] = $rowReqLoan["req_status"];
				$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["req_status"]][0][$lang_locale];
				$arrayReq["LOANPERMIT_AMT"] = $rowReqLoan["loanpermit_amt"];
				$arrayReq["DIFFOLD_CONTRACT"] = $rowReqLoan["diff_old_contract"];
				$arrayReq["RECEIVE_NET"] = $rowReqLoan["receive_net"];
				$arrayReq["BOOKBANK_IMG"] = $rowReqLoan["bookbank_img"];
				$arrayReq["BOOKCOOP_IMG"] = $rowReqLoan["bookcoop_img"];
				$arrayReq["SALARY_IMG"] = $rowReqLoan["salary_img"];
				$arrayReq["CITIZEN_IMG"] = $rowReqLoan["citizen_img"];
				$arrayReq["REMARK"] = $rowReqLoan["remark"];
				$arrayReq["CONTRACTDOC_URL"] = $rowReqLoan["contractdoc_url"];
				$arrayReq["APPROVE_DATE"] = isset($rowReqLoan["approve_date"]) && $rowReqLoan["approve_date"] != "" ? $lib->convertdate($rowReqLoan["approve_date"],'d m Y') : null;
				$arrayReq["INT_RATE_AT_REQ"] = $rowReqLoan["int_rate_at_req"];
				$arrayReq["SALARY_AT_REQ"] = $rowReqLoan["salary_at_req"];
				$arrayReq["REQUEST_DATE"] = $rowReqLoan["request_date"];
				$arrayReq["DEPTACCOUNT_NO_BANK"] = $rowReqLoan["deptaccount_no_bank"];
				$arrayReq["BANK_DESC"] = $rowReqLoan["bank_desc"];
				$arrayReq["DEPTACCOUNT_NO_COOP"] = $rowReqLoan["deptaccount_no_coop"];
				$arrayReq["OBJECTIVE"] = $rowReqLoan["objective"];
				$arrayReq["PAY_DATE"] = $rowReqLoan["pay_date"];
				$arrayReq["EXTRA_CREDIT_PROJECT"] = $rowReqLoan["extra_credit_project"];
				$arrGrpReq[] = $arrayReq;
			}
		}else{
			$fetchReqLoan = $conmysql->prepare("SELECT rl.reqloan_doc,rl.loantype_code,rl.request_amt,rl.period_payment,rl.period,req_status,rl.loanpermit_amt,
											rl.diff_old_contract,rl.receive_net,rl.bookbank_img,rl.bookcoop_img,rl.salary_img,rl.citizen_img,rl.remark,rl.approve_date,rl.contractdoc_url,
											rl.int_rate_at_req,rl.salary_at_req,rl.request_date,rl.deptaccount_no_bank,rl.bank_desc,rl.deptaccount_no_coop,rl.objective,pay_date,ep.extra_credit_name as extra_credit_project
											FROM gcreqloan  rl
											LEFT JOIN gcconstantextracreditproject ep ON rl.extra_credit_project = ep.id_extra_credit
											WHERE rl.member_no = :member_no ORDER BY rl.update_date DESC");
			$fetchReqLoan->execute([':member_no' => $payload["member_no"]]);
			while($rowReqLoan = $fetchReqLoan->fetch(PDO::FETCH_ASSOC)){
				$getLoanType = $conmssql->prepare("SELECT LOANTYPE_DESC FROM lnloantype WHERE loantype_code = :loantype_code");
				$getLoanType->execute([':loantype_code' => $rowReqLoan["loantype_code"]]);
				$rowLoan = $getLoanType->fetch(PDO::FETCH_ASSOC);
				$arrayReq = array();
				$arrayReq["LOANTYPE_DESC"] = $rowLoan["LOANTYPE_DESC"];
				$arrayReq["REQLOAN_DOC"] = $rowReqLoan["reqloan_doc"];
				$arrayReq["LOANTYPE_CODE"] = $rowReqLoan["loantype_code"];
				$arrayReq["REQUEST_AMT"] = $rowReqLoan["request_amt"];
				$arrayReq["PERIOD_PAYMENT"] = $rowReqLoan["period_payment"];
				$arrayReq["PERIOD"] = $rowReqLoan["period"];
				$arrayReq["REQ_STATUS"] = $rowReqLoan["req_status"];
				$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["req_status"]][0][$lang_locale];
				$arrayReq["LOANPERMIT_AMT"] = $rowReqLoan["loanpermit_amt"];
				$arrayReq["DIFFOLD_CONTRACT"] = $rowReqLoan["diff_old_contract"];
				$arrayReq["RECEIVE_NET"] = $rowReqLoan["receive_net"];
				$arrayReq["BOOKBANK_IMG"] = $rowReqLoan["bookbank_img"];
				$arrayReq["BOOKCOOP_IMG"] = $rowReqLoan["bookcoop_img"];
				$arrayReq["SALARY_IMG"] = $rowReqLoan["salary_img"];
				$arrayReq["CITIZEN_IMG"] = $rowReqLoan["citizen_img"];
				$arrayReq["REMARK"] = $rowReqLoan["remark"];
				$arrayReq["CONTRACTDOC_URL"] = $rowReqLoan["contractdoc_url"];
				$arrayReq["APPROVE_DATE"] = isset($rowReqLoan["approve_date"]) && $rowReqLoan["approve_date"] != "" ? $lib->convertdate($rowReqLoan["approve_date"],'d m Y') : null;
				$arrayReq["INT_RATE_AT_REQ"] = $rowReqLoan["int_rate_at_req"];
				$arrayReq["SALARY_AT_REQ"] = $rowReqLoan["salary_at_req"];
				$arrayReq["REQUEST_DATE"] = $rowReqLoan["request_date"];
				$arrayReq["DEPTACCOUNT_NO_BANK"] = $rowReqLoan["deptaccount_no_bank"];
				$arrayReq["BANK_DESC"] = $rowReqLoan["bank_desc"];
				$arrayReq["DEPTACCOUNT_NO_COOP"] = $rowReqLoan["deptaccount_no_coop"];
				$arrayReq["OBJECTIVE"] = $rowReqLoan["objective"];
				$arrayReq["PAY_DATE"] = $rowReqLoan["pay_date"];
				$arrayReq["EXTRA_CREDIT_PROJECT"] = $rowReqLoan["extra_credit_project"];
				$arrGrpReq[] = $arrayReq;
			}
		}
		$arrayResult['REQ_LIST'] = $arrGrpReq;
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
		":error_desc" => "Êè§ Argument ÁÒäÁè¤Ãº "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ä¿Åì ".$filename." Êè§ Argument ÁÒäÁè¤ÃºÁÒá¤è "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>
