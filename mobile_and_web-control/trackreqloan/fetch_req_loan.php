<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestTrack')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGrpReq = array();
		if(isset($dataComing["req_status"]) && $dataComing["req_status"] != ""){
			$fetchReqLoan = $conoracle->prepare("SELECT LOANREQUEST_DOCNO,LOANTYPE_CODE,LOANCREDIT_AMT,LOANREQUEST_AMT,LOANREQUEST_STATUS,
															EXPENSE_ACCID FROM LNREQLOAN WHERE MEMBER_NO = :member_no AND LOANTYPE_CODE = '18' AND LOANREQUEST_STATUS = :req_status and TO_CHAR(LOANREQUEST_DATE,'YYYY-MM-DD') >= '2021-08-31'");
			$fetchReqLoan->execute([
				':member_no' => $member_no,
				':req_status' => $dataComing["req_status"]
			]);
			while($rowReqLoan = $fetchReqLoan->fetch(PDO::FETCH_ASSOC)){
				$getLoanType = $conoracle->prepare("SELECT LOANTYPE_DESC FROM lnloantype WHERE loantype_code = :loantype_code");
				$getLoanType->execute([':loantype_code' => $rowReqLoan["LOANTYPE_CODE"]]);
				$rowLoan = $getLoanType->fetch(PDO::FETCH_ASSOC);
				$arrayReq = array();
				$arrayReq["LOANTYPE_DESC"] = $rowLoan["LOANTYPE_DESC"];
				$arrayReq["REQLOAN_DOC"] = $rowReqLoan["LOANREQUEST_DOCNO"];
				$arrayReq["LOANTYPE_CODE"] = $rowReqLoan["LOANTYPE_CODE"];
				$arrayReq["REQUEST_AMT"] = $rowReqLoan["LOANREQUEST_AMT"];
				/*if($rowReqLoan["period_payment"] > 0){
					$arrayReq["PERIOD_PAYMENT"] = $rowReqLoan["period_payment"];
				}
				if($rowReqLoan["period"] > 0){
					$arrayReq["PERIOD"] = $rowReqLoan["period"];
				}*/
				$arrayReq["REQ_STATUS"] = $rowReqLoan["LOANREQUEST_STATUS"];
				$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["LOANREQUEST_STATUS"]][0][$lang_locale];
				$arrayReq["LOANPERMIT_AMT"] = $rowReqLoan["LOANCREDIT_AMT"];
				/*if($rowReqLoan["diff_old_contract"] > 0){
					$arrayReq["DIFFOLD_CONTRACT"] = $rowReqLoan["diff_old_contract"];
				}*/
				//$arrayReq["RECEIVE_NET"] = $rowReqLoan["receive_net"];
				
				//$arrayReq["REMARK"] = $rowReqLoan["remark"];
				$getPayDate = $conoracle->prepare("SELECT TO_CHAR(LOANRCVFIX_DATE,'YYYYMMDD') as LOANRCVFIX_DATE FROM lnreqloan WHERE loanrequest_docno  = :reqloan_doc");
				$getPayDate->execute([':reqloan_doc' => $rowReqLoan["LOANREQUEST_DOCNO"]]);
				$rowPayDate = $getPayDate->fetch(PDO::FETCH_ASSOC);
				if(date("Ymd") < $rowPayDate["LOANRCVFIX_DATE"]){
					if($rowReqLoan["LOANREQUEST_STATUS"] == '-9' || $rowReqLoan["LOANREQUEST_STATUS"] == '9'){
						$arrayReq['ALLOW_CANCEL'] = FALSE;
					}else{
						$arrayReq['ALLOW_CANCEL'] = TRUE;
					}
				}else{
					$arrayReq['ALLOW_CANCEL'] = FALSE;
				}
				//$arrayReq["APPROVE_DATE"] = isset($rowReqLoan["approve_date"]) && $rowReqLoan["approve_date"] != "" ? $lib->convertdate($rowReqLoan["approve_date"],'d m Y') : null;
				$arrGrpReq[] = $arrayReq;
			}
		}else{
			$fetchReqLoan = $conoracle->prepare("SELECT LOANREQUEST_DOCNO,LOANTYPE_CODE,LOANCREDIT_AMT,LOANREQUEST_AMT,LOANREQUEST_STATUS,
															EXPENSE_ACCID FROM lnreqloan WHERE member_no = :member_no and loantype_code = '18' and TO_CHAR(LOANREQUEST_DATE,'YYYY-MM-DD') >= '2021-08-31'");
			$fetchReqLoan->execute([':member_no' => $member_no]);
			while($rowReqLoan = $fetchReqLoan->fetch(PDO::FETCH_ASSOC)){
				$getLoanType = $conoracle->prepare("SELECT LOANTYPE_DESC FROM lnloantype WHERE loantype_code = :loantype_code");
				$getLoanType->execute([':loantype_code' => $rowReqLoan["LOANTYPE_CODE"]]);
				$rowLoan = $getLoanType->fetch(PDO::FETCH_ASSOC);
				$arrayReq = array();
				$arrayReq["LOANTYPE_DESC"] = $rowLoan["LOANTYPE_DESC"];
				$arrayReq["REQLOAN_DOC"] = $rowReqLoan["LOANREQUEST_DOCNO"];
				$arrayReq["LOANTYPE_CODE"] = $rowReqLoan["LOANTYPE_CODE"];
				$arrayReq["REQUEST_AMT"] = $rowReqLoan["LOANREQUEST_AMT"];
				/*if($rowReqLoan["period_payment"] > 0){
					$arrayReq["PERIOD_PAYMENT"] = $rowReqLoan["period_payment"];
				}
				if($rowReqLoan["period"] > 0){
					$arrayReq["PERIOD"] = $rowReqLoan["period"];
				}*/
				$arrayReq["REQ_STATUS"] = $rowReqLoan["LOANREQUEST_STATUS"];
				$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["LOANREQUEST_STATUS"]][0][$lang_locale];
				$arrayReq["LOANPERMIT_AMT"] = $rowReqLoan["LOANCREDIT_AMT"];
				/*if($rowReqLoan["diff_old_contract"] > 0){
					$arrayReq["DIFFOLD_CONTRACT"] = $rowReqLoan["diff_old_contract"];
				}*/
				/*$arrayReq["RECEIVE_NET"] = $rowReqLoan["receive_net"];
				$arrayReq["REMARK"] = $rowReqLoan["remark"];*/
				$getPayDate = $conoracle->prepare("SELECT TO_CHAR(LOANRCVFIX_DATE,'YYYYMMDD') as LOANRCVFIX_DATE FROM lnreqloan WHERE loanrequest_docno  = :reqloan_doc");
				$getPayDate->execute([':reqloan_doc' => $rowReqLoan["LOANREQUEST_DOCNO"]]);
				$rowPayDate = $getPayDate->fetch(PDO::FETCH_ASSOC);
				if(date("Ymd") < $rowPayDate["LOANRCVFIX_DATE"]){
					if($rowReqLoan["LOANREQUEST_STATUS"] == '-9' || $rowReqLoan["LOANREQUEST_STATUS"] == '9'){
						$arrayReq['ALLOW_CANCEL'] = FALSE;
					}else{
						$arrayReq['ALLOW_CANCEL'] = TRUE;
					}
				}else{
					$arrayReq['ALLOW_CANCEL'] = FALSE;
				}
				//$arrayReq["APPROVE_DATE"] = isset($rowReqLoan["approve_date"]) && $rowReqLoan["approve_date"] != "" ? $lib->convertdate($rowReqLoan["approve_date"],'d m Y') : null;
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