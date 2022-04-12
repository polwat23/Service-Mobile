<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loantype_code'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		if(isset($dataComing["request_amt"]) && $dataComing["request_amt"] != "" && isset($dataComing["period"]) && $dataComing["period"] != ""){
			$fetchLoanIntRate = $conoracle->prepare("SELECT lnd.INTEREST_RATE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
													ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
													WHERE lnt.loantype_code = :loantype_code and SYSDATE BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE ORDER BY lnt.loantype_code");
			$fetchLoanIntRate->execute([':loantype_code' => $dataComing["loantype_code"]]);
			$rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC);
			$period_payment = round($dataComing["request_amt"] * (($rowIntRate["INTEREST_RATE"] /100) / 12) / (1 - (exp(($dataComing["period"] * (-1)) * log((1 + (($rowIntRate["INTEREST_RATE"] /100) / 12)))))));
			if($period_payment % 100 > 0){
				$period_payment =$period_payment  + (100 - ($period_payment % 100));
			}
			
			$arrayResult["RECEIVE_NET"] = $dataComing["request_amt"];
			$arrayResult["PERIOD"] = $dataComing["period"];
			$arrayResult["PERIOD_PAYMENT"] = $period_payment;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$maxloan_amt = 0;
			$oldBal = 0;
			$loanRequest = TRUE;
			if(file_exists(__DIR__.'/../credit/calculate_loan_'.$dataComing["loantype_code"].'.php')){
				include(__DIR__.'/../credit/calculate_loan_'.$dataComing["loantype_code"].'.php');
			}else{
				include(__DIR__.'/../credit/calculate_loan_etc.php');
			}
			if($maxloan_amt <= 0){
				$arrayResult['RESPONSE_CODE'] = "WS0084";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$request_amt = $dataComing["request_amt"] ?? $maxloan_amt;
			if($request_amt < $oldBal){
				$request_amt = $oldBal;
			}
			$getMaxPeriod = $conoracle->prepare("SELECT MAX_PERIOD 
															FROM lnloantype lnt LEFT JOIN lnloantypeperiod lnd ON lnt.LOANTYPE_CODE = lnd.LOANTYPE_CODE
															WHERE :request_amt >= lnd.MONEY_FROM and :request_amt <= lnd.MONEY_TO and lnd.LOANTYPE_CODE = :loantype_code");
			$getMaxPeriod->execute([
				':request_amt' => $maxloan_amt,
				':loantype_code' => $dataComing["loantype_code"]
			]);
			$rowMaxPeriod = $getMaxPeriod->fetch(PDO::FETCH_ASSOC);
			if(isset($rowMaxPeriod["MAX_PERIOD"])){
				$fetchLoanIntRate = $conoracle->prepare("SELECT lnd.INTEREST_RATE,lnt.LOANGROUP_CODE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
														ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
														WHERE lnt.loantype_code = :loantype_code and SYSDATE BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE ORDER BY lnt.loantype_code");
				$fetchLoanIntRate->execute([':loantype_code' => $dataComing["loantype_code"]]);
				$rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC);
				$period_payment = round($maxloan_amt * (($rowIntRate["INTEREST_RATE"] /100) / 12) / (1 - (exp(($rowMaxPeriod["MAX_PERIOD"] * (-1)) * log((1 + (($rowIntRate["INTEREST_RATE"] /100) / 12)))))));
				$period_payment =$period_payment  + (100 - ($period_payment % 100));
				
				//อัปโหลดไฟล์เเนบ
				$arrayUploadFileGroup = array();
				if(isset($rowIntRate["LOANGROUP_CODE"]) && $rowIntRate["LOANGROUP_CODE"] != ''){
					$fetchConstUploadFile = $conmysql->prepare("SELECT fmap.filemapping_id, fmap.file_id, fmap.loangroup_code, fmap.max, fmap.is_require, fmap.update_date,
														fatt.file_name
														FROM gcreqfileattachmentmapping fmap 
														LEFT JOIN gcreqfileattachment fatt ON fmap.file_id = fatt.file_id
														WHERE fmap.is_use = '1' AND fmap.loangroup_code = :loangroup_code");
					$fetchConstUploadFile->execute([
						":loangroup_code" => $rowIntRate["LOANGROUP_CODE"]
					]);
					while($rowConstUploadFile = $fetchConstUploadFile->fetch(PDO::FETCH_ASSOC)){
						$arrConst = array();
						$arrConst["FILEMAPPING_ID"] = $rowConstUploadFile["filemapping_id"];
						$arrConst["FILE_ID"] = $rowConstUploadFile["file_id"];
						$arrConst["FILE_NAME"] = $rowConstUploadFile["file_name"];
						$arrConst["LOANGROUP_CODE"] = $rowConstUploadFile["loangroup_code"];
						$arrConst["MAX"] = $rowConstUploadFile["max"];
						$arrConst["IS_REQUIRE"] = $rowConstUploadFile["is_require"] == "1";
						$arrConst["UPDATE_DATE"] = $rowConstUploadFile["update_date"];
						$arrayUploadFileGroup[] = $arrConst;
					}
				}

				
				$arrayResult["DIFFOLD_CONTRACT"] = $oldBal;
				$arrayResult["RECEIVE_NET"] = $maxloan_amt;
				$arrayResult["REQUEST_AMT"] = $request_amt;
				$arrayResult["LOAN_PERMIT_AMT"] = $maxloan_amt;
				$arrayResult["MAX_PERIOD"] = $rowMaxPeriod["MAX_PERIOD"];
				$arrayResult["PERIOD_PAYMENT"] = $period_payment;
				$arrayResult["SPEC_REMARK"] =  $configError["SPEC_REMARK"][0][$lang_locale];
				$arrayResult["SALARY"] = $dataComing["salary"];
				$arrayResult["SALARY_INPUT_NOTE"] = "กรุณากรอกเงินเดือนรวมกับเงินได้อื่นๆ";
				$arrayResult["IS_INPUT_SALARY"] = TRUE;
				$arrayResult["REQ_REMAIN_SALARY"] = TRUE;
				$arrayResult["IS_REMAIN_SALARY"] = TRUE;
				$arrayResult["REQ_SALARY"] = FALSE;
				$arrayResult["REQ_CITIZEN"] = TRUE;
				$arrayResult["IS_UPLOAD_CITIZEN"] = TRUE;
				$arrayResult["IS_UPLOAD_SALARY"] = FALSE;
				$arrayResult['UPLOADFILE_GRP'] = $arrayUploadFileGroup;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0088";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
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
