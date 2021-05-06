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
			$typeCalDate = $func->getConstant("process_keep_forward");
			$pay_date = date("Y-m-t", strtotime('last day of '.$typeCalDate.' month',strtotime(date('Y-m-d'))));
			$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
			if($typeCalDate == "next"){
				$dayOfMonth = date('d',strtotime($pay_date)) + (date("t") - date("d"));
			}else{
				$dayOfMonth = date('d',strtotime($pay_date)) - date("d");
			}
			$period_payment = ($dataComing["request_amt"] / $dataComing["period"]);// + (($dataComing["request_amt"] * ($rowIntRate["INTEREST_RATE"] / 100) * $dayOfMonth) / $dayinYear);
			$period_payment = (int)$period_payment - ($period_payment % 100) + 100;
			if($dataComing["recv_acc"] == '0'){
				$arrGrpBank = array();
				$getBank = $conoracle->prepare("SELECT TRIM(BANK_DESC) as BANK_DESC,BANK_CODE FROM CMUCFBANK 
											WHERE bank_code IN('006') and USE_FLAG = '1' ORDER BY SETSORT ASC");
				$getBank->execute();
				while($rowBank = $getBank->fetch(PDO::FETCH_ASSOC)){
					$arrBank = array();
					$arrBank["BANK_DESC"] = $rowBank["BANK_DESC"];
					$arrBank["BANK_CODE"] = $rowBank["BANK_CODE"];
					$arrGrpBank[] = $arrBank;
				}
				$arrayResult['COOP_ACCOUNT'] = [];
				$arrayResult["REQ_BOOKBANK"] = FALSE;
				$arrayResult["REQ_BOOKCOOP"] = FALSE;
				$arrayResult["REQ_BANK_ACCOUNT"] = FALSE;
				$arrayResult["IS_UPLOAD_BOOKBANK"] = FALSE;
				$arrayResult["IS_UPLOAD_BOOKCOOP"] = FALSE;
				$arrayResult["IS_BANK_ACCOUNT"] = FALSE;
				$arrayResult['BANK'] = $arrGrpBank;
			}else if($dataComing["recv_acc"] == '1'){
				$arrGrpCoopAcc = array();
				$formatDept = $func->getConstant('dep_format');
				$getAccount = $conoracle->prepare("SELECT deptaccount_no FROM dpdeptmaster
													WHERE member_no = :member_no and deptclose_status <> 1 AND depttype_code IN('00','88') ORDER BY deptaccount_no ASC");
				$getAccount->execute([':member_no' => $member_no]);
				while($rowAccount = $getAccount->fetch(PDO::FETCH_ASSOC)){
					$arrCoopAcc = array();
					$arrCoopAcc["ACCOUNT_NO"] = $rowAccount["DEPTACCOUNT_NO"];
					$arrCoopAcc["ACCOUNT_NO_FORMAT"] =  $lib->formataccount($rowAccount["DEPTACCOUNT_NO"],$formatDept);
					$arrGrpCoopAcc[] = $arrCoopAcc;
				}
				$arrayResult['COOP_ACCOUNT'] = $arrGrpCoopAcc;
				$arrayResult["REQ_BOOKBANK"] = FALSE;
				$arrayResult["REQ_BOOKCOOP"] = FALSE;
				$arrayResult["REQ_BANK_ACCOUNT"] = FALSE;
				$arrayResult["IS_UPLOAD_BOOKBANK"] = FALSE;
				$arrayResult["IS_UPLOAD_BOOKCOOP"] = FALSE;
				$arrayResult["IS_BANK_ACCOUNT"] = FALSE;
				$arrayResult['BANK'] = [];
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
			$getMaxPeriod = $conoracle->prepare("SELECT MAX_PERIOD 
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
				$arrGrpBank = array();
				while($rowLoanObj = $getLoanObjective->fetch(PDO::FETCH_ASSOC)){
					$arrObj = array();
					$arrObj["LOANOBJECTIVE_CODE"] = $rowLoanObj["LOANOBJECTIVE_CODE"];
					$arrObj["LOANOBJECTIVE_DESC"] = $rowLoanObj["LOANOBJECTIVE_DESC"];
					$arrGrpObj[] = $arrObj;
				}
				$getBank = $conoracle->prepare("SELECT TRIM(BANK_DESC) as BANK_DESC,BANK_CODE FROM CMUCFBANK 
											WHERE bank_code IN('006') and USE_FLAG = '1' ORDER BY SETSORT ASC");
				$getBank->execute();
				while($rowBank = $getBank->fetch(PDO::FETCH_ASSOC)){
					$arrBank = array();
					$arrBank["BANK_DESC"] = $rowBank["BANK_DESC"];
					$arrBank["BANK_CODE"] = $rowBank["BANK_CODE"];
					$arrGrpBank[] = $arrBank;
				}
				/*$arrGrpCoopAcc = array();
				$formatDept = $func->getConstant('dep_format');
				$getAccount = $conoracle->prepare("SELECT deptaccount_no FROM dpdeptmaster
													WHERE member_no = :member_no and deptclose_status <> 1 ORDER BY deptaccount_no ASC");
				$getAccount->execute([':member_no' => $member_no]);
				while($rowAccount = $getAccount->fetch(PDO::FETCH_ASSOC)){
					$arrCoopAcc = array();
					$arrCoopAcc["ACCOUNT_NO"] = $rowAccount["DEPTACCOUNT_NO"];
					$arrCoopAcc["ACCOUNT_NO_FORMAT"] =  $lib->formataccount($rowAccount["DEPTACCOUNT_NO"],$formatDept);
					$arrGrpCoopAcc[] = $arrCoopAcc;
				}*/
				$fetchLoanIntRate = $conoracle->prepare("SELECT lnd.INTEREST_RATE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
														ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
														WHERE lnt.loantype_code = :loantype_code and SYSDATE BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE ORDER BY lnt.loantype_code");
				$fetchLoanIntRate->execute([':loantype_code' => $dataComing["loantype_code"]]);
				$rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC);
				$typeCalDate = $func->getConstant("process_keep_forward");
				$pay_date = date("Y-m-t", strtotime('last day of '.$typeCalDate.' month',strtotime(date('Y-m-d'))));
				$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
				if($typeCalDate == "next"){
					$dayOfMonth = date('d',strtotime($pay_date)) + (date("t") - date("d"));
				}else{
					$dayOfMonth = date('d',strtotime($pay_date)) - date("d");
				}
				$period_payment = ($maxloan_amt / $rowMaxPeriod["MAX_PERIOD"]);// + (($maxloan_amt * ($rowIntRate["INTEREST_RATE"] / 100) * $dayOfMonth) / $dayinYear);
				$period_payment = (int)$period_payment - ($period_payment % 100) + 100;
				$arrRecvBank["VALUE"] = "0";
				$arrRecvBank["DESC"] = "โอนเข้าบัญชีธนาคาร";
				$arrGrpReceive[] = $arrRecvBank;
				$arrRecvCoop["VALUE"] = "1";
				$arrRecvCoop["DESC"] = "โอนเข้าบัญชีสหกรณ์";
				$arrGrpReceive[] = $arrRecvCoop;
				$arrayResult["DEFAULT_RECV_ACC"] = "0";
				$arrayResult["RECEIVE_NET"] = $maxloan_amt;
				$arrayResult["REQUEST_AMT"] = $request_amt;
				$arrayResult["LOAN_PERMIT_AMT"] = $maxloan_amt;
				$arrayResult["MAX_PERIOD"] = $rowMaxPeriod["MAX_PERIOD"];
				$arrayResult["PERIOD_PAYMENT"] = $period_payment;
				$arrayResult["TERMS_HTML"]["uri"] = "https://policy.gensoft.co.th/".((explode('-',$config["COOP_KEY"]))[0] ?? $config["COOP_KEY"])."/termanduse.html";
				$arrayResult["SPEC_REMARK"] =  $configError["SPEC_REMARK"][0][$lang_locale];
				$arrayResult["RECV_ACC"] = $arrGrpReceive;
				$arrayResult["REQ_SALARY"] = FALSE;
				$arrayResult["REQ_CITIZEN"] = FALSE;
				$arrayResult["REQ_BANK_ACCOUNT"] = FALSE;
				$arrayResult["REQ_BOOKBANK"] = FALSE;
				$arrayResult["REQ_BOOKCOOP"] = FALSE;
				$arrayResult["IS_UPLOAD_CITIZEN"] = FALSE;
				$arrayResult["IS_UPLOAD_SALARY"] = FALSE;
				$arrayResult["IS_UPLOAD_BOOKBANK"] = FALSE;
				$arrayResult["IS_UPLOAD_BOOKCOOP"] = FALSE;
				$arrayResult["IS_BANK_ACCOUNT"] = TRUE;
				$arrayResult['OBJECTIVE'] = $arrGrpObj;
				$arrayResult['BANK'] = $arrGrpBank;
				$arrayResult['COOP_ACCOUNT'] = $arrGrpCoopAcc;
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