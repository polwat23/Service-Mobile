<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loantype_code'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		//get loangroup_code
		$getLoanGroup = $conmssql->prepare("SELECT LOANGROUP_CODE FROM lnloantype WHERE loantype_code = :loantype_code");
		$getLoanGroup->execute([
			':loantype_code' => $dataComing["loantype_code"]
		]);
		$rowLoanGroup = $getLoanGroup->fetch(PDO::FETCH_ASSOC);
		
		if(isset($dataComing["request_amt"]) && $dataComing["request_amt"] != "" && isset($dataComing["period"]) && $dataComing["period"] != ""){
			$oldBal = 0;
			if(file_exists(__DIR__.'/../credit/calculate_loan_'.$rowLoanGroup["LOANGROUP_CODE"].'.php')){
				include(__DIR__.'/../credit/calculate_loan_'.$rowLoanGroup["LOANGROUP_CODE"].'.php');
			}else{
				include(__DIR__.'/../credit/calculate_loan_etc.php');
			}
			if($dataComing["recv_acc"] == '0'){
				$arrGrpBank = array();
				$getBank = $conmssql->prepare("SELECT (BANK_DESC) as BANK_DESC,BANK_CODE FROM CMUCFBANK 
											WHERE bank_code IN('002','004','014') and USE_FLAG = '1' ORDER BY SETSORT ASC");
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
				$arrayResult["IS_UPLOAD_BOOKBANK"] = TRUE;
				$arrayResult["IS_UPLOAD_BOOKCOOP"] = FALSE;
				$arrayResult["IS_BANK_ACCOUNT"] = TRUE;
				$arrayResult['BANK'] = $arrGrpBank;
			}else if($dataComing["recv_acc"] == '1'){
				$arrayResult["REQ_BOOKBANK"] = FALSE;
				$arrayResult["REQ_BOOKCOOP"] = FALSE;
				$arrayResult["REQ_BANK_ACCOUNT"] = FALSE;
				$arrayResult["IS_UPLOAD_BOOKBANK"] = FALSE;
				$arrayResult["IS_UPLOAD_BOOKCOOP"] = TRUE;
				$arrayResult["IS_BANK_ACCOUNT"] = FALSE;
				$arrayResult['BANK'] = [];
			}
			
			if($dataComing["request_amt"] < $oldBal){
				$arrayResult['RESPONSE_CODE'] = "WS0086";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
			$arrayResult["IS_EXTRA_CREDIT"] = TRUE;
				
			if($rowLoanGroup["LOANGROUP_CODE"] == '02'){
				
				//ดึงวันที่จ่ายเงินกู้
				$getDiffPayDate = $conmysql->prepare("SELECT DATEDIFF(loanpaydate,now()) as diff_loanpaydate,loanpaydate 
												FROM gcconstantloanpaydate 
												WHERE loanpaydate > now() AND is_use = '1' ORDER BY loanpaydate ASC LIMIT 1");
				$getDiffPayDate->execute();
				$rowDiffPayDate = $getDiffPayDate->fetch(PDO::FETCH_ASSOC);
				if(isset($rowDiffPayDate["loanpaydate"]) && $rowDiffPayDate["loanpaydate"] != ""){
					$get_paydate = $rowDiffPayDate["loanpaydate"];
				}
				
				//คำนวณดอกเบี้ย
				$fetchLoanIntRate = $conmssql->prepare("SELECT lnd.INTEREST_RATE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
													ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
													WHERE lnt.loantype_code = :loantype_code and GETDATE() BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE ORDER BY lnt.loantype_code");
				$fetchLoanIntRate->execute([':loantype_code' => $dataComing["loantype_code"]]);
				$rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC);
				$typeCalDate = $func->getConstant("process_keep_forward");
				$pay_date = $get_paydate ?? date("Y-m-t", strtotime('last day of '.$typeCalDate.' month',strtotime(date('Y-m-d'))));
				$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
				if($typeCalDate == "next"){
					$dayOfMonth = date('d',strtotime($pay_date)) + (date("t") - date("d"));
				}else{
					$dayOfMonth = date('d',strtotime($pay_date)) - date("d");
				}
				$period_payment = ($dataComing["request_amt"] / $dataComing["period"]) + (($dataComing["request_amt"] * ($rowIntRate["INTEREST_RATE"] / 100) * $dayOfMonth) / $dayinYear);
				$period_payment = (int)$period_payment - ($period_payment % 100) + 100;
			}else{
				$period_payment = $dataComing["request_amt"] / $dataComing["period"];
			}
			$receive_net = $dataComing["request_amt"] - $oldBal;
			if($receive_net < 0){
				$arrayResult['RESPONSE_CODE'] = "WS0086";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}else{
				$arrayResult["RECEIVE_NET"] = $receive_net;
			}
			$arrayResult["DIFFOLD_CONTRACT"] = $oldBal;
			$arrayResult["LOAN_PERMIT_BALANCE"] = $maxloan_amt - $dataComing["request_amt"];
			$arrayResult["PERIOD"] = $dataComing["period"];
			$arrayResult["PERIOD_PAYMENT"] = ceil($period_payment);
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$maxloan_amt = 0;
			$oldBal = 0;
			$loanRequest = TRUE;
			if(file_exists(__DIR__.'/../credit/calculate_loan_'.$rowLoanGroup["LOANGROUP_CODE"].'.php')){
				include(__DIR__.'/../credit/calculate_loan_'.$rowLoanGroup["LOANGROUP_CODE"].'.php');
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
				$arrayResult["DIFFOLD_CONTRACT"] = $oldBal;
				$arrayResult["LOAN_PERMIT_AMT"] = $maxloan_amt;
				$arrayResult["REQUEST_AMT"] = $request_amt;
				$arrayResult['RESPONSE_CODE'] = "WS0086";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
			
			$getMaxPeriod = $conmssql->prepare("SELECT MAX_PERIOD 
															FROM lnloantype lnt LEFT JOIN lnloantypeperiod lnd ON lnt.LOANTYPE_CODE = lnd.LOANTYPE_CODE
															WHERE :request_amt_1 >= lnd.MONEY_FROM and :request_amt_2 < lnd.MONEY_TO and lnd.LOANTYPE_CODE = :loantype_code");
			$getMaxPeriod->execute([
				':request_amt_1' => $maxloan_amt,
				':request_amt_2' => $maxloan_amt,
				':loantype_code' => $dataComing["loantype_code"]
			]);
			$rowMaxPeriod = $getMaxPeriod->fetch(PDO::FETCH_ASSOC);
			if(isset($rowMaxPeriod["MAX_PERIOD"])){
				$getLoanObjective = $conmssql->prepare("SELECT LOANOBJECTIVE_CODE,LOANOBJECTIVE_DESC FROM lnucfloanobjective");
				$getLoanObjective->execute();
				$arrGrpObj = array();
				$arrGrpBank = array();
				while($rowLoanObj = $getLoanObjective->fetch(PDO::FETCH_ASSOC)){
					$arrObj = array();
					$arrObj["LOANOBJECTIVE_CODE"] = $rowLoanObj["LOANOBJECTIVE_CODE"];
					$arrObj["LOANOBJECTIVE_DESC"] = $rowLoanObj["LOANOBJECTIVE_DESC"];
					$arrGrpObj[] = $arrObj;
				}
				$getBank = $conmssql->prepare("SELECT (BANK_DESC) as BANK_DESC,BANK_CODE FROM CMUCFBANK 
											WHERE bank_code IN('002','004','014') and USE_FLAG = '1' ORDER BY SETSORT ASC");
				$getBank->execute();
				while($rowBank = $getBank->fetch(PDO::FETCH_ASSOC)){
					$arrBank = array();
					$arrBank["BANK_DESC"] = $rowBank["BANK_DESC"];
					$arrBank["BANK_CODE"] = $rowBank["BANK_CODE"];
					$arrGrpBank[] = $arrBank;
				}

				//ดึงข้อมูลโครงการพิเศษ
				$arrayGroupExtraCredit = array();
				$fetchExtraCredit = $conmysql->prepare("SELECT id_extra_credit, extra_credit_name, extra_credit_desc FROM gcconstantextracreditproject WHERE is_use = '1' ORDER BY create_date DESC");
				$fetchExtraCredit->execute();
				while($rowExtraCredit = $fetchExtraCredit->fetch(PDO::FETCH_ASSOC)){
					$arrExtraCredit = array();
					$arrExtraCredit["ID_EXTRA_CREDIT"] = $rowExtraCredit["id_extra_credit"];
					$arrExtraCredit["EXTRA_CREDIT_NAME"] = $rowExtraCredit["extra_credit_name"];
					$arrExtraCredit["EXTRA_CREDIT_DESC"] = $rowExtraCredit["extra_credit_desc"];
					$arrayGroupExtraCredit[] = $arrExtraCredit;
				}
				$arrayResult["IS_EXTRA_CREDIT"] = TRUE;
				$arrayResult["EXTRA_CREDIT_LIST"] = $arrayGroupExtraCredit;
					
				if($rowLoanGroup["LOANGROUP_CODE"] == '02'){
				
					//ดึงวันที่จ่ายเงินกู้
					$getDiffPayDate = $conmysql->prepare("SELECT DATEDIFF(loanpaydate,now()) as diff_loanpaydate,loanpaydate 
													FROM gcconstantloanpaydate 
													WHERE loanpaydate > now() AND is_use = '1' ORDER BY loanpaydate ASC LIMIT 1");
					$getDiffPayDate->execute();
					$rowDiffPayDate = $getDiffPayDate->fetch(PDO::FETCH_ASSOC);
					if(isset($rowDiffPayDate["loanpaydate"]) && $rowDiffPayDate["loanpaydate"] != ""){
						$get_paydate = $rowDiffPayDate["loanpaydate"];
					}
					
					//คำนวณดอกเบี้ย
					$fetchLoanIntRate = $conmssql->prepare("SELECT lnd.INTEREST_RATE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
														ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
														WHERE lnt.loantype_code = :loantype_code and GETDATE() BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE ORDER BY lnt.loantype_code");
					$fetchLoanIntRate->execute([':loantype_code' => $dataComing["loantype_code"]]);
					$rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC);
					$typeCalDate = $func->getConstant("process_keep_forward");
					$pay_date = $get_paydate ?? date("Y-m-t", strtotime('last day of '.$typeCalDate.' month',strtotime(date('Y-m-d'))));
					$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
					if($typeCalDate == "next"){
						$dayOfMonth = date('d',strtotime($pay_date)) + (date("t") - date("d"));
					}else{
						$dayOfMonth = date('d',strtotime($pay_date)) - date("d");
					}
					$period_payment = ($maxloan_amt / $rowMaxPeriod["MAX_PERIOD"]) + (($maxloan_amt * ($rowIntRate["INTEREST_RATE"] / 100) * $dayOfMonth) / $dayinYear);
					$period_payment = (int)$period_payment - ($period_payment % 100) + 100;
				}else{
					$period_payment = $maxloan_amt / $rowMaxPeriod["MAX_PERIOD"];
				}
				$arrayResult["DIFFOLD_CONTRACT"] = $oldBal;
				$arrRecvBank["VALUE"] = "0";
				$arrRecvBank["DESC"] = "โอนเข้าบัญชีธนาคาร";
				$arrGrpReceive[] = $arrRecvBank;
				$arrRecvCoop["VALUE"] = "1";
				$arrRecvCoop["DESC"] = "โอนเข้าบัญชีสหกรณ์";
				$arrGrpReceive[] = $arrRecvCoop;
				$arrayResult["DEFAULT_RECV_ACC"] = "0";
				$arrayResult["RECEIVE_NET"] = $receive_net - $oldBal;;
				$arrayResult["REQUEST_AMT"] = $request_amt;
				$arrayResult["LOAN_PERMIT_BALANCE"] = $maxloan_amt - $request_amt;
				$arrayResult["LOAN_PERMIT_AMT"] = $maxloan_amt;
				$arrayResult["MAX_PERIOD"] = $rowMaxPeriod["MAX_PERIOD"];
				$arrayResult["DISABLE_PERIOD"] = TRUE;
				$arrayResult["PERIOD_PAYMENT"] = ceil($period_payment);
				$arrayResult["TERMS_HTML"]["uri"] = "https://policy.gensoft.co.th/".((explode('-',$config["COOP_KEY"]))[0] ?? $config["COOP_KEY"])."/termanduse.html";
				$arrayResult["SPEC_REMARK"] =  $configError["SPEC_REMARK"][0][$lang_locale];
				$arrayResult["RECV_ACC"] = $arrGrpReceive;
				$arrayResult["REQ_SALARY"] = FALSE;
				$arrayResult["REQ_CITIZEN"] = FALSE;
				$arrayResult["REQ_BANK_ACCOUNT"] = FALSE;
				$arrayResult["REQ_OBJECTIVE"] = TRUE;
				$arrayResult["IS_UPLOAD_CITIZEN"] = FALSE;
				$arrayResult["IS_UPLOAD_SALARY"] = FALSE;
				$arrayResult["IS_BANK_ACCOUNT"] = TRUE;
				$arrayResult["BANK_ACCOUNT_REMARK"] = 'หากไม่ใช่บัญชีเงินเดือน กรุณาแนบหน้า Bookbank (ต้องเป็นบัญชีผู้กู้)';
				$arrayResult['OBJECTIVE'] = $arrGrpObj;
				$arrayResult['BANK'] = $arrGrpBank;
				if($dayremainEnd == 0){
					$arrayResult["NOTE_DESC"] = "เงินต้นและดอกเบี้ยของท่าน ณ วันที่กู้จะถูกหักรวมกับยอดปันผล-เฉลี่ยคืนที่ท่านจะได้รับ";
					$arrayResult["NOTE_DESC_COLOR"] = "red";
				}
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