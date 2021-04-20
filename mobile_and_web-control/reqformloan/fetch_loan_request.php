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
																WHERE lnt.loantype_code IN('".implode(',',$arrCanCal)."') and SYSDATE >= lnd.EFFECTIVE_DATE ORDER BY lnt.loantype_code");
		$fetchLoanIntRate->execute();
		while($rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC)){
			$arrayDetailLoan = array();
			$CheckIsReq = $conmysql->prepare("SELECT reqloan_doc,req_status
														FROM gcreqloan WHERE loantype_code = :loantype_code and member_no = :member_no and req_status NOT IN('-9','9','1')");
			$CheckIsReq->execute([
				':loantype_code' => $rowIntRate["LOANTYPE_CODE"],
				':member_no' => $payload["member_no"]
			]);
			if($CheckIsReq->rowCount() > 0){
				$rowIsReq = $CheckIsReq->fetch(PDO::FETCH_ASSOC);
				$arrayDetailLoan["FLAG_NAME"] = $configError["REQ_FLAG_DESC"][0][$lang_locale];
				$arrayDetailLoan["IS_REQ"] = FALSE;
				$arrayDetailLoan["REQ_STATUS"] = $configError["REQ_LOAN_STATUS"][0][$rowIsReq["req_status"]][0][$lang_locale];
			}else{
				if($rowIntRate["LOANTYPE_CODE"] == '2J'){
					$getMemb = $conoracle->prepare("SELECT SALARY_AMOUNT,MEMBER_DATE,trunc(MONTHS_BETWEEN(ADD_MONTHS(birth_date, 720),sysdate),0) as REMAIN_PERIOD, TRIM(EMP_TYPE) as EMP_TYPE
											FROM mbmembmaster WHERE member_no = :member_no");
					$getMemb->execute([':member_no' => $member_no]);
					$rowMemb = $getMemb->fetch(PDO::FETCH_ASSOC);
					$month_member = $lib->count_duration($rowMemb["MEMBER_DATE"],'m');
					$age_member = intval($month_member);
					if($rowMemb["EMP_TYPE"] == '01' || $rowMemb["EMP_TYPE"] == '10' || $rowMemb["EMP_TYPE"] == '02' || $rowMemb["EMP_TYPE"] == '05' || $rowMemb["EMP_TYPE"] == '03' || $rowMemb["EMP_TYPE"] == '04' || $rowMemb["EMP_TYPE"] == '09'){
						if($age_member >= 6){
							$arrayDetailLoan["IS_REQ"] = TRUE;
						}else{
							$arrayDetailLoan["FLAG_NAME"] = "ต้องเป็นสมาชิกอย่างน้อย 6 เดือนขึ้นไป";
							$arrayDetailLoan["IS_REQ"] = FALSE;
						}
					}else{
						$arrayDetailLoan["FLAG_NAME"] = "ประเภทสมาชิกของท่าน ไม่สามารถขอกู้ประเภทนี้ได้";
						$arrayDetailLoan["IS_REQ"] = FALSE;
					}
				}else{
					$arrayDetailLoan["IS_REQ"] = TRUE;
				}
			}
			$arrayDetailLoan["LOANTYPE_CODE"] = $rowIntRate["LOANTYPE_CODE"];
			$arrayDetailLoan["LOANTYPE_DESC"] = $rowIntRate["LOANTYPE_DESC"];
			$arrayDetailLoan["INT_RATE"] = number_format($rowIntRate["INTEREST_RATE"],3);
			$arrGrpLoan[] = $arrayDetailLoan;
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