<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayGrpLoan = array();
		$getLoantype = $conoracle->prepare("SELECT loantype_code,loantype_alias_name FROM gcconstanttypeloan WHERE is_loanrequest = '1'");
		$getLoantype->execute();
		while($rowLoantype = $getLoantype->fetch(PDO::FETCH_ASSOC)){
			$arrayLoan = array();
			$arrayLoan["LOANTYPE_CODE"] = $rowLoantype["LOANTYPE_CODE"];
			$getLoanTypeData = $conoracle->prepare("SELECT ln.LOANTYPE_DESC,lnt.interest_rate,lp.max_period,ln.LOANGROUP_CODE
													FROM lnloantype ln LEFT JOIN lncfloanintratedet lnt ON ln.inttabrate_code = lnt.loanintrate_code
													and sysdate BETWEEN lnt.effective_date and lnt.expire_date
													LEFT JOIN lnloantypeperiod lp ON ln.loantype_code = lp.loantype_code
													LEFT JOIN lnloantypembtype lm ON ln.loantype_code = lm.loantype_code
													LEFT JOIN mbmembmaster mb ON lm.membtype_code = mb.membtype_code
													WHERE ln.loantype_code = :loantype_code and mb.member_no = :member_no");
			$getLoanTypeData->execute([
				':loantype_code' => $rowLoantype["LOANTYPE_CODE"],
				':member_no' => $member_no
			]);
			$rowLoanData = $getLoanTypeData->fetch(PDO::FETCH_ASSOC);
			if(isset($rowLoanData["LOANTYPE_DESC"])){
				if($rowLoanData["LOANGROUP_CODE"] == '01'){
					$checkRightsLoan = $conoracle->prepare("SELECT LN.LOANTYPE_CODE FROM LNLOANTYPEIVRMOB LN 
															LEFT JOIN MBMEMBMASTER MB ON LN.MEMBTYPE_CODE = MB.MEMBTYPE_CODE
															AND LN.TERMINAL_TYPE = 'MOB' AND LN.USE_FLAG=1 
															and MB.member_no = :member_no and LN.loantype_code = :loantype_code");
					$checkRightsLoan->execute([
						':member_no' => $member_no,
						':loantype_code' => $rowLoantype["LOANTYPE_CODE"]
					]);
					$rowRights = $checkRightsLoan->fetch(PDO::FETCH_ASSOC);
					if(isset($rowRights["LOANTYPE_CODE"]) && $rowRights["LOANTYPE_CODE"] != ""){
						$arrayLoan["LOANTYPE_DESC"] = $rowLoantype["LOANTYPE_ALIAS_NAME"] ?? $rowLoanData["LOANTYPE_DESC"];
						$arrayLoan["MAX_PERIOD"] = $rowLoanData["MAX_PERIOD"];
						$arrayLoan["INT_RATE"] = $rowLoanData["INTEREST_RATE"] ?? 0;
						$arrayLoan['TERMS_URL']  = "";
						if(file_exists(__DIR__.'/../../resource/loan-type/'.$rowLoantype["LOANTYPE_CODE"].'.png')){
							$arrayLoan["LOAN_TYPE_IMG"] = $config["URL_SERVICE"].'resource/loan-type/'.$rowLoantype["LOANTYPE_CODE"].'.png?v='.date('Ym');
						}else{
							$arrayLoan["LOAN_TYPE_IMG"] = null;
						}
						$arrayGrpLoan[] = $arrayLoan;
					}
				}else{
					$arrayLoan["LOANTYPE_DESC"] = $rowLoantype["LOANTYPE_ALIAS_NAME"] ?? $rowLoanData["LOANTYPE_DESC"];
					$arrayLoan["MAX_PERIOD"] = $rowLoanData["MAX_PERIOD"];
					$arrayLoan["INT_RATE"] = $rowLoanData["INTEREST_RATE"] ?? 0;
					$arrayLoan['TERMS_URL']  = "";
					if(file_exists(__DIR__.'/../../resource/loan-type/'.$rowLoantype["LOANTYPE_CODE"].'.png')){
						$arrayLoan["LOAN_TYPE_IMG"] = $config["URL_SERVICE"].'resource/loan-type/'.$rowLoantype["LOANTYPE_CODE"].'.png?v='.date('Ym');
					}else{
						$arrayLoan["LOAN_TYPE_IMG"] = null;
					}
					$arrayGrpLoan[] = $arrayLoan;
				}
			}
		}
		$arrayResult['LOAN_TYPE'] = $arrayGrpLoan;
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
