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
		
		$arrMoratorium = array();
		$fetchMoratorium = $conmysql->prepare("SELECT is_moratorium, loangroup_code FROM gcmoratorium WHERE member_no = :member_no and is_moratorium <> '0'");
		$fetchMoratorium->execute([
			':member_no' => $member_no
		]);
		while($rowMoratorium = $fetchMoratorium->fetch(PDO::FETCH_ASSOC)){
			$arrMoratorium[$rowMoratorium["loangroup_code"]] = $rowMoratorium["is_moratorium"];
		}
		
		$fetchLoanIntRate = $conmssql->prepare("SELECT lnt.LOANTYPE_DESC,lnt.LOANTYPE_CODE,lnd.INTEREST_RATE,lnt.LOANGROUP_CODE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
																	ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
																	WHERE lnt.loantype_code IN(".implode(',',$arrCanCal).") and getdate() BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE ORDER BY lnt.loantype_code");
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
				if(isset($arrMoratorium[$rowIntRate["LOANGROUP_CODE"]])){
					$arrayDetailLoan["IS_REQ"] = FALSE;
					$arrayDetailLoan["FLAG_NAME"] = $arrMoratorium[$rowIntRate["LOANGROUP_CODE"]] == '1' ? "อยู่ระหว่างการพักชำระหนี้ไม่สามารถกู้ได้" : "อยู่ระหว่างรอเจ้าหน้าที่ยืนยันข้อมูลพักชำระหนี้ไม่สามารถกู้ได้";
				}else{
					if($rowIntRate["LOANGROUP_CODE"] == '01'){
						$getMemberIno = $conmssql->prepare("SELECT MEMBER_DATE,SALARY_AMOUNT FROM mbmembmaster WHERE member_no = :member_no");
						$getMemberIno->execute([':member_no' => $member_no]);
						$rowMember = $getMemberIno->fetch(PDO::FETCH_ASSOC);
						$duration_month = $lib->count_duration($rowMember["MEMBER_DATE"],'m');
						if($duration_month >= 2){
							$getOldLoanBal = $conmssql->prepare("SELECT SUM(lnm.PRINCIPAL_BALANCE) as PRINCIPAL_BALANCE FROM lncontmaster lnm
												LEFT JOIN lnloantype lnt ON lnm.loantype_code = lnt.loantype_code  
												WHERE lnm.member_no = :member_no 
												and loangroup_code = :loangroup_code and lnm.contract_status > 0 and lnm.contract_status <> 8");
							$getOldLoanBal->execute([
								':member_no' => $member_no,
								':loangroup_code' => $rowIntRate["LOANGROUP_CODE"]
							]);
							$rowOldLoanBal = $getOldLoanBal->fetch(PDO::FETCH_ASSOC);
							if(isset($rowOldLoanBal["PRINCIPAL_BALANCE"])){
								$arrayDetailLoan["IS_REQ"] = FALSE;
								$arrayDetailLoan["FLAG_NAME"] = "มีหนี้ค้างชำระ กรุณาปิดสัญญา";
							}else{
								$arrayDetailLoan["IS_REQ"] = TRUE;
							}
						}else{
							$arrayDetailLoan["IS_REQ"] = FALSE;
							$arrayDetailLoan["FLAG_NAME"] = "กู้ได้เฉพาะสมาชิก 2 เดือนขึ้นไป";
						}
					}else if($rowIntRate["LOANGROUP_CODE"] == '02'){
						$getMemberIno = $conmssql->prepare("SELECT MEMBER_DATE,SALARY_AMOUNT FROM mbmembmaster WHERE member_no = :member_no");
						$getMemberIno->execute([':member_no' => $member_no]);
						$rowMember = $getMemberIno->fetch(PDO::FETCH_ASSOC);
						$duration_month = $lib->count_duration($rowMember["MEMBER_DATE"],'m');
						if($duration_month >= 4){
							$getOldLoanBal = $conmssql->prepare("SELECT lnm.LAST_PERIODPAY FROM lncontmaster lnm
												LEFT JOIN lnloantype lnt ON lnm.loantype_code = lnt.loantype_code  
												WHERE lnm.member_no = :member_no
												and  lnm.loantype_code = :loantype_code and lnm.contract_status > 0 and lnm.contract_status <> 8");
							$getOldLoanBal->execute([
								':member_no' => $member_no,
								':loantype_code' => $rowIntRate["LOANTYPE_CODE"],
							]);
							$rowOldLoanBal = $getOldLoanBal->fetch(PDO::FETCH_ASSOC);
							if(isset($rowOldLoanBal["LAST_PERIODPAY"]) && $rowOldLoanBal["LAST_PERIODPAY"] <= 2){
								$arrayDetailLoan["IS_REQ"] = FALSE;
								$arrayDetailLoan["FLAG_NAME"] = "ต้องชำระสัญญาเดิม 3 งวดขึ้นไป จึงจะขอกู้ได้";
							}else{
								$arrayDetailLoan["IS_REQ"] = TRUE;
							}
						}else{
							$arrayDetailLoan["IS_REQ"] = FALSE;
							$arrayDetailLoan["FLAG_NAME"] = "กู้ได้เฉพาะสมาชิก 4 เดือนขึ้นไป";
						}
					}else{
						$arrayDetailLoan["IS_REQ"] = TRUE;
					}
				}
			}
			
			$arrayDetailLoan["LOANTYPE_CODE"] = $rowIntRate["LOANTYPE_CODE"];
			$arrayDetailLoan["LOANTYPE_DESC"] = $rowIntRate["LOANTYPE_DESC"];
			$arrayDetailLoan["LOANGROUP_CODE"] = $rowIntRate["LOANGROUP_CODE"];
			$arrayDetailLoan["INT_RATE"] = number_format($rowIntRate["INTEREST_RATE"],2);
			$arrGrpLoan[] = $arrayDetailLoan;
		}
		$arrayResult["LOAN_LIST"] = $arrGrpLoan;
		$arrayResult["arrMoratorium"] = $arrMoratorium;
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
		":error_desc" => "�� Argument �����ú "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "��� ".$filename." �� Argument �����ú���� "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>
