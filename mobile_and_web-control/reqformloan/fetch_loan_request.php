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
											FROM gcreqloan WHERE loantype_code = :loantype_code and 
											member_no = :member_no and req_status NOT IN('-9','9','1')");
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
					$getMemberType = $conoracle->prepare("SELECT MEMBER_TYPE FROM MBMEMBMASTER WHERE MEMBER_NO = :member_no");
					$getMemberType->execute([':member_no' => $member_no]);
					$rowMembType = $getMemberType->fetch(PDO::FETCH_ASSOC);
					if($rowMembType["MEMBER_TYPE"] == '1'){
						$getSharePeriod = $conoracle->prepare("SELECT LAST_PERIOD FROM SHSHAREMASTER WHERE MEMBER_NO = :member_no");
						$getSharePeriod->execute([':member_no' => $member_no]);
						$rowSharePeriod = $getSharePeriod->fetch(PDO::FETCH_ASSOC);
						if($rowSharePeriod["LAST_PERIOD"] >= 6){
							$arrTypeAllow = array();
							$getTypeAllowShow = $conmysql->prepare("SELECT gat.deptaccount_no 
																	FROM gcuserallowacctransaction gat LEFT JOIN gcconstantaccountdept gct 
																	ON gat.id_accountconstant = gct.id_accountconstant
																	WHERE gct.allow_showdetail = '1' and gat.member_no = :member_no and gat.is_use = '1'");
							$getTypeAllowShow->execute([':member_no' => $payload["member_no"]]);
							while($rowTypeAllow = $getTypeAllowShow->fetch(PDO::FETCH_ASSOC)){
								$arrTypeAllow[] = $rowTypeAllow["deptaccount_no"];
							}
							$have_savingaccount = FALSE;
							$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
							$arrDataAPI["MemberID"] = substr($member_no,-6);
							$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/InquiryAccount",$arrDataAPI,$arrHeaderAPI);
							if(!$arrResponseAPI["RESULT"]){
								$have_savingaccount = FALSE;
							}
							$arrResponseAPI = json_decode($arrResponseAPI);
							if($arrResponseAPI->responseCode == "200"){
								foreach($arrResponseAPI->accountDetail as $accData){
									if (in_array($accData->coopAccountNo, $arrTypeAllow) && $accData->accountStatus == "0" && $accData->accountType == "10"){
										$have_savingaccount = TRUE;
									}
								}
							}else{
								$have_savingaccount = FALSE;
							}
							if($have_savingaccount === TRUE){
								$arrayDetailLoan["IS_REQ"] = TRUE;
							}else{
								$arrayDetailLoan["FLAG_NAME"] = $configError["REQ_FLAG_DETAIL"][0]["0"][0][$lang_locale];
								$arrayDetailLoan["IS_REQ"] = FALSE;
							}
						}else{
							$arrayDetailLoan["FLAG_NAME"] = $configError["REQ_FLAG_DETAIL"][0]["1"][0][$lang_locale];
							$arrayDetailLoan["IS_REQ"] = FALSE;
						}
					}else{
						$arrayDetailLoan["FLAG_NAME"] = $configError["REQ_FLAG_DETAIL"][0]["2"][0][$lang_locale];
						$arrayDetailLoan["IS_REQ"] = FALSE;
					}
				}else{
					$arrayDetailLoan["IS_REQ"] = TRUE;
				}
			}
			$arrayDetailLoan["LOANTYPE_CODE"] = $rowIntRate["LOANTYPE_CODE"];
			$arrayDetailLoan["LOANTYPE_DESC"] = $rowIntRate["LOANTYPE_DESC"];
			$arrayDetailLoan["INT_RATE"] = $rowIntRate["INTEREST_RATE"] * 100;
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