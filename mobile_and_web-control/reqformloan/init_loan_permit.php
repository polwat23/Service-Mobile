<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loantype_code'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		if(isset($dataComing["request_amt"]) && $dataComing["request_amt"] != "" && isset($dataComing["period"]) && $dataComing["period"] != ""){
			$oldBal = 0;
			$maxsalary = 0;
			$period_payment = 0;
			if(file_exists(__DIR__.'/../credit/calculate_loan_'.$dataComing["loantype_code"].'.php')){
				include(__DIR__.'/../credit/calculate_loan_'.$dataComing["loantype_code"].'.php');
			}else{
				include(__DIR__.'/../credit/calculate_loan_etc.php');
			}
			$receive_net = $dataComing["request_amt"];
			if($max_period == 0){
				$fetchLoanIntRate = $conoracle->prepare("SELECT lnd.INTEREST_RATE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
														ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
														WHERE lnt.loantype_code = :loantype_code and SYSDATE BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE ORDER BY lnt.loantype_code");
				$fetchLoanIntRate->execute([':loantype_code' => $dataComing["loantype_code"]]);
				$rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC);
				$period = $max_period == 0 ? (string)$dataComing["period"] : (string)$max_period;
				$int_rate = ($rowIntRate["INTEREST_RATE"] / 100);
				$typeCalDate = $func->getConstant("cal_start_pay_date");
				$pay_date = date("Y-m-t", strtotime('last day of '.$typeCalDate.' month',strtotime(date('Y-m-d'))));
				$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
				if($typeCalDate == "next"){
					$dayOfMonth = date('d',strtotime($pay_date)) + (date("t") - date("d"));
				}else{
					$dayOfMonth = date('d',strtotime($pay_date)) - date("d");
				}
				$getPayRound = $conoracle->prepare("SELECT PAYROUND_FACTOR FROM lnloantype WHERE loantype_code = :loantype_code");
				$getPayRound->execute([':loantype_code' => $dataComing["loantype_code"]]);
				$rowPayRound = $getPayRound->fetch(PDO::FETCH_ASSOC);
				$payment_per_period = exp(($period * (-1)) * log(((1 + ($int_rate / 12)))));
				$period_payment = ($dataComing["request_amt"] * ($int_rate / 12) / (1 - ($payment_per_period)));
				$module = 100 - ($period_payment % 100);
				if($module < 100){
					$period_payment = floor($period_payment + $module);
				}
			}
			$arrayResult["RECEIVE_NET"] = $receive_net - $oldBal;
			//$arrayResult["LOAN_PERMIT_BALANCE"] = $maxloan_amt - $dataComing["request_amt"];
			$arrayResult["PERIOD"] = $max_period == 0 ? (string)$dataComing["period"] : (string)$max_period;
			$arrayResult["PERIOD_PAYMENT"] = $period_payment;
			$maxsalary = ($maxsalary * 30) /100 ;
			if($dataComing["loantype_code"] == '38' && $dataComing["salary"] < $maxsalary){
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถขอกู้ได้ เนื่องจากเงินเดือนคงเหลือน้อยกว่า 30 % ";
				$arrayResult['REVERT_VALUE'] = TRUE;
				$arrayResult['RESULT'] = FALSE; 
				require_once('../../include/exit_footer.php');
			}else if($dataComing["loantype_code"] !='42' && $dataComing["loantype_code"] !='38' && $dataComing["salary"] < 5000){						
				$arrayResult['RESULT'] = FALSE; 
				if($dataComing["salary"] == 0){
					$arrayResult['RESPONSE_MESSAGE'] = "กรุณากรอกเงินเดือน";
					$arrayResult['REVERT_VALUE'] = TRUE;
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "เงินเดือนคงเหลือน้อยกว่าหลักเกณฑ์การกู้ขอกู้";
					$arrayResult['REVERT_VALUE'] = TRUE;
				}	
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');		
			}	
		}else{
			$maxloan_amt = 0;
			$maxsalary  = 0;
			$oldBal = 0;
			$loanRequest = TRUE;
			$period_payment = 0;
			if(file_exists(__DIR__.'/../credit/calculate_loan_'.$dataComing["loantype_code"].'.php')){
				include(__DIR__.'/../credit/calculate_loan_'.$dataComing["loantype_code"].'.php');
			}else{
				include(__DIR__.'/../credit/calculate_loan_etc.php');
			}
			$maxloan_amt = floor($maxloan_amt - ($maxloan_amt % 100));
			if($maxloan_amt <= 0){
				$arrayResult['RESPONSE_CODE'] = "WS0084";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$request_amt = $dataComing["request_amt"] ?? $maxloan_amt;
			/*if($request_amt < $oldBal){
				$request_amt = $oldBal;
			}*/
			$getMaxPeriod = $conoracle->prepare("SELECT MAX_PERIOD ,LOANGROUP_CODE
															FROM lnloantype lnt LEFT JOIN lnloantypeperiod lnd ON lnt.LOANTYPE_CODE = lnd.LOANTYPE_CODE
															WHERE :request_amt >= lnd.MONEY_FROM and :request_amt <= lnd.MONEY_TO and lnd.LOANTYPE_CODE = :loantype_code");
			$getMaxPeriod->execute([
				':request_amt' => $maxloan_amt,
				':loantype_code' => $dataComing["loantype_code"]
			]);
			$rowMaxPeriod = $getMaxPeriod->fetch(PDO::FETCH_ASSOC);
			if(isset($rowMaxPeriod["MAX_PERIOD"])){
				$getLoanObjective = $conoracle->prepare("SELECT LOANOBJECTIVE_CODE,LOANOBJECTIVE_DESC FROM lnucfloanobjective WHERE loantype_code = :loantype");
				$getLoanObjective->execute([':loantype' => $dataComing["loantype_code"]]);
				$arrGrpObj = array();
				while($rowLoanObj = $getLoanObjective->fetch(PDO::FETCH_ASSOC)){
					$arrObj = array();
					$arrObj["LOANOBJECTIVE_CODE"] = $rowLoanObj["LOANOBJECTIVE_CODE"];
					$arrObj["LOANOBJECTIVE_DESC"] = $rowLoanObj["LOANOBJECTIVE_DESC"];
					$arrGrpObj[] = $arrObj;
				}
				$typeCalDate = $func->getConstant("cal_start_pay_date");
				if($max_period == 0){
					$fetchLoanIntRate = $conoracle->prepare("SELECT lnd.INTEREST_RATE,lnt.LOANGROUP_CODE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
															ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
															WHERE lnt.loantype_code = :loantype_code and SYSDATE BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE
															ORDER BY lnt.loantype_code");
					$fetchLoanIntRate->execute([':loantype_code' => $dataComing["loantype_code"]]);
					$rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC);
					
					if($dataComing["loantype_code"] == '20' || $dataComing["loantype_code"] == '23'){
						$period = 240;
					}else{
						$period = $max_period == 0 ? (string)$rowMaxPeriod["MAX_PERIOD"] : (string)$max_period;
					}
					$int_rate = ($rowIntRate["INTEREST_RATE"] / 100);
					$typeCalDate = $func->getConstant("cal_start_pay_date");
					$pay_date = date("Y-m-t", strtotime('last day of '.$typeCalDate.' month',strtotime(date('Y-m-d'))));
					$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
					if($typeCalDate == "next"){
						$dayOfMonth = date('d',strtotime($pay_date)) + (date("t") - date("d"));
					}else{
						$dayOfMonth = date('d',strtotime($pay_date)) - date("d");
					}
					$getPayRound = $conoracle->prepare("SELECT PAYROUND_FACTOR FROM lnloantype WHERE loantype_code = :loantype_code");
					$getPayRound->execute([':loantype_code' => $dataComing["loantype_code"]]);
					$rowPayRound = $getPayRound->fetch(PDO::FETCH_ASSOC);
					$payment_per_period = exp(($period * (-1)) * log(((1 + ($int_rate / 12)))));
					$period_payment = ($request_amt * ($int_rate / 12) / (1 - ($payment_per_period)));
					$module = 100 - ($period_payment % 100);
					if($module < 100){
						$period_payment = floor($period_payment + $module);
					}
				}
				

				$iscountcoll = 0;
				if($dataComing["loantype_code"] == '23' || $dataComing["loantype_code"] == '27' || $dataComing["loantype_code"] == '38' || $dataComing["loantype_code"] == '42' || $dataComing["loantype_code"] == '27'){
					$fetchCollReqgrt = $conoracle->prepare("SELECT USEMAN_AMT FROM LNLOANTYPEREQGRT WHERE loantype_code = :loantype_code 
															 AND  :request_amt between money_from AND  money_to");
					$fetchCollReqgrt->execute([':loantype_code' => $dataComing["loantype_code"],
												':request_amt' => $request_amt]);
					$rowCollReqgrt =  $fetchCollReqgrt->fetch(PDO::FETCH_ASSOC);				
					$arrayResult["IS_GUARANTEE"] = TRUE;
					$iscountcoll = $rowCollReqgrt["USEMAN_AMT"];
					$arrayResult["GUARANTOR"] = $rowCollReqgrt["USEMAN_AMT"];
				}
				
				if($dataComing["loantype_code"] == '28' || $dataComing["loantype_code"] == '43' || $dataComing["loantype_code"] == '33'){
					$arrayResult['NOTE_DESC'] = "หมายเหตุ :  ยอดวงกู้จะได้ไม่เกิน 80% ของราคาประเมิน";
					$arrayResult['NOTE_DESC_COLOR'] = "red";
				}
				//อัปโหลดไฟล์เเนบ
				
				$arrayUploadFileGroup = array();
				if(isset($rowIntRate["LOANGROUP_CODE"]) && $rowIntRate["LOANGROUP_CODE"] != '' ){
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
						if($rowConstUploadFile["file_id"] =='7'){
							$arrConst["MAX"] = $rowCollReqgrt["USEMAN_AMT"];
						}else{
							$arrConst["MAX"] = $rowConstUploadFile["max"];
						}
						$arrConst["LOANGROUP_CODE"] = $rowConstUploadFile["loangroup_code"];
						$arrConst["IS_REQUIRE"] = $rowConstUploadFile["is_require"] == "1";
						$arrConst["UPDATE_DATE"] = $rowConstUploadFile["update_date"];
						$arrayUploadFileGroup[] = $arrConst;
					}
				}
				
				//$arrayResult["DIFFOLD_CONTRACT"] = $oldBal == 0 ? null : $oldBal;
				$arrayResult["LOANREQ_AMT_STEP"] = 100;
				$arrayResult["RECEIVE_NET"] = $maxloan_amt - $oldBal;
				$arrayResult["REQUEST_AMT"] = (string)$request_amt;
				//$arrayResult["PAY_DATE"] = $lib->convertdate(date("Y-m-t", strtotime('last day of '.$typeCalDate.' month',strtotime(date('Y-m-d')))),'d M Y');
				$arrayResult["LOAN_PERMIT_BALANCE"] = $maxloan_amt - $request_amt;
				$arrayResult["LOAN_PERMIT_AMT"] = $maxloan_amt;
				$arrayResult["MAX_PERIOD"] = $period ?? "0";
				$arrayResult["PERIOD_PAYMENT"] = $period_payment;
				//$arrayResult["OPTION_PAYTYPE"] = $arrGrpPayType;
				$arrayResult["SPEC_REMARK"] =  $configError["SPEC_REMARK"][0][$lang_locale];
				if($rowIntRate["LOANGROUP_CODE"] !="01"){
					$arrayResult['NOTE_DESC'] = "หมายเหตุ :  เงินกู้ประเภทที่มีคนค้ำกรุณาเเนบสำเนาบัตรผู้ค้ำให้ครบตามจำนวนผู้ค้ำ";
					$arrayResult['NOTE_DESC_COLOR'] = "red";
				}
				$arrayResult["SALARY"] = $dataComing["salary"];
				$arrayResult["REMARK"] = $dataComing["remark"];
				$arrayResult["REQ_REMAIN_SALARY"] = TRUE;
				$arrayResult["IS_INPUT_REMARK"] = TRUE;
				$arrayResult["IS_INPUT_SALARY"] = TRUE;
				$arrayResult["REQ_SALARY"] = TRUE;
				$arrayResult["REQ_CITIZEN"] = TRUE;
				$arrayResult["IS_UPLOAD_CITIZEN"] = FALSE;
				$arrayResult["IS_UPLOAD_SALARY"] = FALSE;		
				$arrayResult["HIDE_PERIOD_INFO"] = TRUE;
				$arrayResult["HIDE_PERIOD_PAYMENT"] = TRUE;
				$arrayResult["HIDE_LOAN_PERMIT_BALANCE"] = TRUE;
				$arrayResult["HIDE_RECEIVE_NET"] = TRUE;
				$arrayResult['UPLOADFILE_GRP'] = $arrayUploadFileGroup;		
				$arrayResult['OBJECTIVE'] = $arrGrpObj;			
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
