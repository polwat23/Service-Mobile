<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loantype_code'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		if(isset($dataComing["request_amt"]) && $dataComing["request_amt"] != "" && isset($dataComing["period"]) && $dataComing["period"] != ""){
			$oldBal = 0;
			if(file_exists(__DIR__.'/../credit/calculate_loan_'.$dataComing["loantype_code"].'.php')){
				include(__DIR__.'/../credit/calculate_loan_'.$dataComing["loantype_code"].'.php');
			}else{
				include(__DIR__.'/../credit/calculate_loan_etc.php');
			}
			if($maxloan_amt <= 0){
				$arrayResult['RESPONSE_CODE'] = "WS0084";
				$arrayResult['RESPONSE_MESSAGE'] = $cal_remark ?? $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$fetchLoanIntRate = $conoracle->prepare("SELECT * FROM (SELECT lnd.INTEREST_RATE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
														ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
														WHERE lnt.loantype_code = :loantype_code ORDER BY lnd.effective_date DESC) WHERE rownum <= 1");
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
			$period_payment = ($dataComing["request_amt"] / $dataComing["period"]);// + ((($dataComing["request_amt"] * $rowIntRate["INTEREST_RATE"]) * $dayOfMonth) / $dayinYear);
			$receive_net = $dataComing["request_amt"] - $oldBal;
			if($period_payment < 1000 && $dataComing["loantype_code"] == '2J'){
				$temp_period = ($dataComing["request_amt"] / 1000);
				$min_period = ceil($temp_period);
				$arrayResult["PERIOD"] = $min_period;
				$arrayResult["PERIOD_PAYMENT"] = 1000;
			}else{
				$period_payment = (int)$period_payment;
				$arrayResult["PERIOD"] = $dataComing["period"];
				$arrayResult["PERIOD_PAYMENT"] = $period_payment;
			}
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
				$arrayResult["REQ_BOOKBANK"] = TRUE;
				$arrayResult["REQ_BOOKCOOP"] = FALSE;
				$arrayResult["REQ_BANK_ACCOUNT"] = TRUE;
				$arrayResult["IS_UPLOAD_BOOKBANK"] = TRUE;
				$arrayResult["IS_UPLOAD_BOOKCOOP"] = FALSE;
				$arrayResult["IS_BANK_ACCOUNT"] = FALSE;
				$arrayResult['BANK'] = $arrGrpBank;
			}else if($dataComing["recv_acc"] == '1'){
				$arrGrpCoopAcc = array();
				$arrTypeAllow = array();
				$getTypeAllowShow = $conmysql->prepare("SELECT gat.deptaccount_no 
														FROM gcuserallowacctransaction gat LEFT JOIN gcconstantaccountdept gct ON gat.id_accountconstant = gct.id_accountconstant
														WHERE gct.allow_showdetail = '1' and gat.member_no = :member_no and gat.is_use = '1'");
				$getTypeAllowShow->execute([':member_no' => $payload["member_no"]]);
				while($rowTypeAllow = $getTypeAllowShow->fetch(PDO::FETCH_ASSOC)){
					$arrTypeAllow[] = $rowTypeAllow["deptaccount_no"];
				}
				$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
				$arrDataAPI["MemberID"] = substr($member_no,-6);
				$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/InquiryAccount",$arrDataAPI,$arrHeaderAPI);
				if(!$arrResponseAPI["RESULT"]){
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS9999",
						":error_desc" => "Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/InquiryAccount",
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไฟล์ ".$filename." Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/InquiryAccount";
					$lib->sendLineNotify($message_error);
					$func->MaintenanceMenu($dataComing["menu_component"]);
					$arrayResult['RESPONSE_CODE'] = "WS9999";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
				$arrResponseAPI = json_decode($arrResponseAPI);
				$formatDept = $func->getConstant('dep_format');
				$formatDeptHidden = $func->getConstant('hidden_dep');
				if($arrResponseAPI->responseCode == "200"){
					foreach($arrResponseAPI->accountDetail as $accData){
						if (in_array($accData->coopAccountNo, $arrTypeAllow) && $accData->accountStatus == "0" && $accData->accountType == '10'){
							$arrCoopAcc = array();
							$arrCoopAcc["ACCOUNT_NO"] = $accData->coopAccountNo;
							$arrCoopAcc["ACCOUNT_NO_FORMAT"] =  $lib->formataccount($accData->coopAccountNo,$formatDept);
							$arrGrpCoopAcc[] = $arrCoopAcc;
						}
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS9001";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
				
				if($dataComing["loantype_code"] == '2J'){
					$arrayResult['BANK'] = [];
				}else {
					$arrayResult["IS_UPLOAD_SALARY"] = TRUE;
					$arrayResult["REQ_SALARY"] = TRUE;
					$arrayResult['BANK'] = $arrGrpBank;
				}
				$arrayResult['COOP_ACCOUNT'] = $arrGrpCoopAcc;
				$arrayResult["REQ_BOOKBANK"] = FALSE;
				$arrayResult["REQ_CITIZEN"] = TRUE;
				$arrayResult["IS_UPLOAD_CITIZEN"] = TRUE;
				$arrayResult["REQ_BANK_ACCOUNT"] = FALSE;
				$arrayResult["IS_UPLOAD_BOOKBANK"] = FALSE;
				$arrayResult["IS_BANK_ACCOUNT"] = FALSE;
				$arrayResult['BANK'] = [];
			}
			
			//เช็คอัปโหลดเงินเดือน
			if($dataComing["loantype_code"] == '2J'){
				$arrayResult["REQ_BOOKCOOP"] = TRUE;
				$arrayResult["IS_UPLOAD_BOOKCOOP"] = TRUE;	
				if($dataComing["request_amt"] > 50000){
					$arrayResult["IS_UPLOAD_SALARY"] = TRUE;
					$arrayResult["REQ_SALARY"] = TRUE;				
					$arrayResult["NOTE_DESC"] = "การกู้เงินจำนวนเกินกว่า 50,000 บาท ให้แสกนใบรับเงินได้ 1 เดือนล่าสุด (ณ วันที่ยื่นกู้) และต้องมีลายเซ็นผู้กู้รับรอง และเมื่อหักชำระหนี้เงินกู้ทุกประเภทแล้ว จะต้องมียอดรับเงินสุทธิคงเหลือไม่ต่ำกว่า 5,000 บาท";
					$arrayResult["NOTE_DESC_COLOR"] = "red";
				}else{
					$arrayResult["IS_UPLOAD_SALARY"] = FALSE;
					$arrayResult["REQ_SALARY"] = FALSE;
					$arrayResult["NOTE_DESC"] = null;
					$arrayResult["NOTE_DESC_COLOR"] = null;
				}
			}else{
				$arrayResult["REQ_BOOKCOOP"] = FALSE;
				$arrayResult["IS_UPLOAD_BOOKCOOP"] = FALSE;
			}
			//ยอดชำระต่องวดต่ำกว่า 1000 2J
			$min_period = null;
			if($period_payment < 1000 && $dataComing["loantype_code"] == '2J'){
				$temp_period = ($dataComing["request_amt"] / 1000);
				$min_period = ceil($temp_period);
				$arrayResult["PERIOD"] = $min_period;
				$arrayResult["PERIOD_PAYMENT"] = 1000;
			}else{
				$period_payment = (int)$period_payment;
				$arrayResult["PERIOD"] = $dataComing["period"];
				$arrayResult["PERIOD_PAYMENT"] = $period_payment;
			}
			if($dataComing["request_amt"] >= 50000){
				if($dataComing["remain_salary"] - $arrayResult["PERIOD_PAYMENT"] >= 5000){
					$arrayResult["RECEIVE_NET"] = $receive_net;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0120";
					$arrayResult['RESPONSE_MESSAGE'] = $cal_remark ?? $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}else{
				$arrayResult["IS_REMAIN_SALARY"] = FALSE;
				$arrayResult["RECEIVE_NET"] = $receive_net;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}
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
				$arrayResult['RESPONSE_MESSAGE'] = $cal_remark ?? $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$request_amt = $dataComing["request_amt"] ?? $maxloan_amt;
			
			//เงินกู้ 2J มีหักกลบ
			if($dataComing["loantype_code"] == '2J' && $request_amt < $oldBal){
				if($oldBal > $maxloan_amt){
					$request_amt = $maxloan_amt;
				}else{
					$request_amt = $oldBal;
				}
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
				$arrGrpCoopAcc = array();
				$arrTypeAllow = array();
				$getTypeAllowShow = $conmysql->prepare("SELECT gat.deptaccount_no 
														FROM gcuserallowacctransaction gat LEFT JOIN gcconstantaccountdept gct 
														ON gat.id_accountconstant = gct.id_accountconstant
														WHERE gct.allow_showdetail = '1' and gat.member_no = :member_no and gat.is_use = '1'");
				$getTypeAllowShow->execute([':member_no' => $payload["member_no"]]);
				while($rowTypeAllow = $getTypeAllowShow->fetch(PDO::FETCH_ASSOC)){
					$arrTypeAllow[] = $rowTypeAllow["deptaccount_no"];
				}
				$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
				$arrDataAPI["MemberID"] = substr($member_no,-6);
				$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/InquiryAccount",$arrDataAPI,$arrHeaderAPI);
				if(!$arrResponseAPI["RESULT"]){
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS9999",
						":error_desc" => "Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/InquiryAccount",
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไฟล์ ".$filename." Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/InquiryAccount";
					$lib->sendLineNotify($message_error);
					$func->MaintenanceMenu($dataComing["menu_component"]);
					$arrayResult['RESPONSE_CODE'] = "WS9999";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
				$arrResponseAPI = json_decode($arrResponseAPI);
				$formatDept = $func->getConstant('dep_format');
				$formatDeptHidden = $func->getConstant('hidden_dep');
				if($arrResponseAPI->responseCode == "200"){
					foreach($arrResponseAPI->accountDetail as $accData){
						if (in_array($accData->coopAccountNo, $arrTypeAllow) && $accData->accountStatus == "0" && $accData->accountType == "10"){
							$arrCoopAcc = array();
							$arrCoopAcc["ACCOUNT_NO"] = $accData->coopAccountNo;
							$arrCoopAcc["ACCOUNT_NO_FORMAT"] = $lib->formataccount($accData->coopAccountNo,$formatDept);
							$arrGrpCoopAcc[] = $arrCoopAcc;
						}
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS9001";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
				$fetchLoanIntRate = $conoracle->prepare("SELECT * FROM (SELECT lnd.INTEREST_RATE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
														ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
														WHERE lnt.loantype_code = :loantype_code ORDER BY lnd.effective_date DESC) WHERE rownum <= 1");
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
				$period_payment = ($maxloan_amt / $rowMaxPeriod["MAX_PERIOD"]);
				//$interest = ((($maxloan_amt * $rowIntRate["INTEREST_RATE"]) * $dayOfMonth) / $dayinYear);
				//$period_payment += $interest;
				//ยอดชำระต่องวดต่ำกว่า 1000 2J
				$min_period = null;
				if($period_payment < 1000 && $dataComing["loantype_code"] == '2J'){
					$temp_period = ($maxloan_amt / 1000);
					$min_period = ceil($temp_period);
					$arrayResult["PERIOD"] = $min_period;
					$arrayResult["PERIOD_PAYMENT"] = 1000;
				}else{
					$period_payment = (int)$period_payment - ($period_payment % 100);
					$arrayResult["PERIOD_PAYMENT"] = $period_payment;
				}
				
				$receive_net = $maxloan_amt - $oldBal;
				
				if($dataComing["loantype_code"] != '2J'){
					$arrRecvBank["VALUE"] = "0";
					$arrRecvBank["DESC"] = "โอนเข้าบัญชีธนาคาร";
					$arrGrpReceive[] = $arrRecvBank;
				}
				$arrRecvCoop["VALUE"] = "1";
				$arrRecvCoop["DESC"] = "โอนเข้าบัญชีสหกรณ์";
				$arrGrpReceive[] = $arrRecvCoop;
				if($dataComing["loantype_code"] == '2J'){
					$arrayResult["DEFAULT_RECV_ACC"] = "1";
				}else {
					$arrayResult["DEFAULT_RECV_ACC"] = "0";
				}
				$arrayResult["RECEIVE_NET"] = $receive_net;
				$arrayResult["REQUEST_AMT"] = $request_amt;
				$arrayResult["LOAN_PERMIT_AMT"] = $maxloan_amt;
				$arrayResult["MAX_PERIOD"] = $rowMaxPeriod["MAX_PERIOD"];
				$arrayResult["TERMS_HTML"]["uri"] = "https://policy.gensoft.co.th/".((explode('-',$config["COOP_KEY"]))[0] ?? $config["COOP_KEY"])."/termanduse.html";
				$arrayResult["SPEC_REMARK"] =  $configError["SPEC_REMARK"][0][$lang_locale];
				$arrayResult["RECV_ACC"] = $arrGrpReceive;
				$arrayResult["REQ_CITIZEN"] = TRUE;
				$arrayResult["REQ_BANK_ACCOUNT"] = FALSE;
				$arrayResult["REQ_BOOKBANK"] = FALSE;
				$arrayResult["IS_UPLOAD_CITIZEN"] = TRUE;
				$arrayResult["IS_UPLOAD_BOOKBANK"] = FALSE;
				$arrayResult["IS_BANK_ACCOUNT"] = FALSE;
				$arrayResult['OBJECTIVE'] = $arrGrpObj;
				if($dataComing["loantype_code"] == '2J'){
					$arrayResult["REQ_BOOKCOOP"] = TRUE;
					$arrayResult["IS_UPLOAD_BOOKCOOP"] = TRUE;	
					if($request_amt > 50000){
						$arrayResult["IS_REMAIN_SALARY"] = TRUE;
						$arrayResult["IS_UPLOAD_SALARY"] = TRUE;
						$arrayResult["REQ_SALARY"] = TRUE;		
						$arrayResult["NOTE_DESC"] = "การกู้เงินจำนวนเกินกว่า 50,000 บาท ให้แสกนใบรับเงินได้ 1 เดือนล่าสุด (ณ วันที่ยื่นกู้) และต้องมีลายเซ็นผู้กู้รับรอง และเมื่อหักชำระหนี้เงินกู้ทุกประเภทแล้ว จะต้องมียอดรับเงินสุทธิคงเหลือไม่ต่ำกว่า 5,000 บาท";
						$arrayResult["NOTE_DESC_COLOR"] = "red";
					}else{
						$arrayResult["IS_UPLOAD_SALARY"] = FALSE;
						$arrayResult["REQ_SALARY"] = FALSE;
						$arrayResult["NOTE_DESC"] = null;
						$arrayResult["NOTE_DESC_COLOR"] = null;
					}
					$arrayResult['BANK'] = [];
				}else {
					$arrayResult["IS_UPLOAD_SALARY"] = TRUE;
					$arrayResult["REQ_SALARY"] = TRUE;
					$arrayResult["REQ_BOOKCOOP"] = FALSE;
					$arrayResult["IS_UPLOAD_BOOKCOOP"] = FALSE;
					$arrayResult['BANK'] = $arrGrpBank;
				}
				$arrayResult['COOP_ACCOUNT'] = $arrGrpCoopAcc;
				$arrayResult['LOANREQ_AMT_STEP'] = 100;
				$arrayResult["DIFFOLD_CONTRACT"] = $oldBal;
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