<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGrpLoan = array();
		$arrCanCal = array();
		$fetchLoanCanCal = $conmysql->prepare("SELECT loantype_code FROM gcconstanttypeloan WHERE is_loanrequest = '1'");
		$fetchLoanCanCal->execute();
		while($rowCanCal = $fetchLoanCanCal->fetch(PDO::FETCH_ASSOC)){
			$arrCanCal[] = $rowCanCal["loantype_code"];
		}
		$fetchLoanIntRate = $conoracle->prepare("SELECT lnt.LOANTYPE_DESC,lnt.LOANTYPE_CODE,lnd.INTEREST_RATE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
																ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
																WHERE lnt.loantype_code IN(".implode(',',$arrCanCal).") and SYSDATE BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE ");
		$fetchLoanIntRate->execute();
		while($rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC)){
			$fetchCredit = $conoracle->prepare("SELECT lc.maxloan_amt,lc.percentshare,lc.percentsalary,mb.salary_amount,(sh.sharestk_amt*10) as SHARE_AMT
														FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,mbmembmaster mb 
														LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no
														WHERE mb.member_no = :member_no and LT.LOANTYPE_CODE = :loantype_code
														and TRUNC(MONTHS_BETWEEN (SYSDATE,mb.member_date ) /12 *12) BETWEEN lc.startmember_time and lc.endmember_time");
			$fetchCredit->execute([
				':member_no' => $member_no,
				':loantype_code' => $rowIntRate["LOANTYPE_CODE"]
			]);
			$rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC);
			if($rowCredit["MAXLOAN_AMT"] > 0){
				$arrayDetailLoan = array();
				$CheckIsReq = $conmysql->prepare("SELECT reqloan_doc,req_status
															FROM gcreqloan WHERE loantype_code = :loantype_code and member_no = :member_no and req_status <> '-9'");
				$CheckIsReq->execute([
					':loantype_code' => $rowIntRate["LOANTYPE_CODE"],
					':member_no' => $member_no
				]);
				if($CheckIsReq->rowCount() > 0){
					$rowIsReq = $CheckIsReq->fetch(PDO::FETCH_ASSOC);
					$arrayDetailLoan["IS_REQ"] = TRUE;
					if($rowIsReq["req_status"] == '8'){
						$arrayDetailLoan["REQ_STATUS"] = "รอลงรับ";
					}else if($rowIsReq["req_status"] == '1'){
						$arrayDetailLoan["REQ_STATUS"] = "อนุมัติ";
					}else if($rowIsReq["req_status"] == '7'){
						$arrayDetailLoan["REQ_STATUS"] = "ลงรับรอตรวจสิทธิ์เพิ่มเติม";
					}
				}else{
					$arrayDetailLoan["IS_REQ"] = FALSE;
				}
				$arrayDetailLoan["LOANTYPE_CODE"] = $rowIntRate["LOANTYPE_CODE"];
				$arrayDetailLoan["LOANTYPE_DESC"] = $rowIntRate["LOANTYPE_DESC"];
				$arrayDetailLoan["INT_RATE"] = number_format($rowIntRate["INTEREST_RATE"] * 100,2).' %';
				$arrGrpLoan[] = $arrayDetailLoan;
			}
		}
		$arrayResult["LOAN_LIST"] = $arrGrpLoan;
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