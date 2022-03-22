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
				/*$arrGrpBank = array();
				$getBank = $conmssql->prepare("SELECT (BANK_DESC) as BANK_DESC,BANK_CODE FROM CMUCFBANK 
											WHERE bank_code IN('002','004','014') and USE_FLAG = '1' ORDER BY SETSORT ASC");
				$getBank->execute();
				while($rowBank = $getBank->fetch(PDO::FETCH_ASSOC)){
					$arrBank = array();
					$arrBank["BANK_DESC"] = $rowBank["BANK_DESC"];
					$arrBank["BANK_CODE"] = $rowBank["BANK_CODE"];
					$arrGrpBank[] = $arrBank;
				}*/
				//เลขบัญชีธนาคาร
				$fetchBankInfo = $conmssql->prepare("SELECT mb.EXPENSE_BANK, mb.EXPENSE_CODE, mb.EXPENSE_ACCID,cmb.BANK_DESC FROM MBMEMBMASTER mb
												LEFT JOIN cmucfbank cmb ON cmb.BANK_CODE = mb.EXPENSE_BANK
												WHERE mb.MEMBER_NO = :member_no");
				$fetchBankInfo->execute([':member_no' => $member_no]);
				$rowBankInfo = $fetchBankInfo->fetch(PDO::FETCH_ASSOC);
				$arrayResult["EXPENSE_BANK"] = $rowBankInfo["EXPENSE_BANK"];
				$arrayResult["EXPENSE_BANK_DESC"] = $rowBankInfo["BANK_DESC"];
				$arrayResult["EXPENSE_CODE"] = $rowBankInfo["EXPENSE_CODE"];
				$arrayResult["EXPENSE_ACCID"] = $rowBankInfo["EXPENSE_ACCID"];
				
				$arrayResult['COOP_ACCOUNT'] = [];
				$arrayResult["REQ_BOOKBANK"] = FALSE;
				$arrayResult["REQ_BOOKCOOP"] = FALSE;
				$arrayResult["REQ_BANK_ACCOUNT"] = FALSE;
				$arrayResult["IS_UPLOAD_BOOKBANK"] = FALSE;
				$arrayResult["IS_UPLOAD_BOOKCOOP"] = FALSE;
				$arrayResult["IS_BANK_ACCOUNT"] = FALSE;
				//$arrayResult['BANK'] = $arrGrpBank;
			}else if($dataComing["recv_acc"] == '1'){
				$arrayResult["EXPENSE_BANK"] = "";
				$arrayResult["EXPENSE_BANK_DESC"] = "";
				$arrayResult["EXPENSE_CODE"] = "";
				$arrayResult["EXPENSE_ACCID"] = "";
				
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
				$period_payment = $dataComing["request_amt"] / $dataComing["period"];
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
				$arrayResult["RECEIVE_NET"] = $dataComing["request_amt"];
			}
			$arrayResult["TEMP_PERMIT_AMT"] = $maxloan_amt;
			$arrayResult["DIFFOLD_CONTRACT"] = $oldBal;
			$arrayResult['OLD_GROUPBAL_01'] = $oldGroupBal01;
			$arrayResult['OLD_GROUPBAL_02'] = $oldGroupBal02;
			$arrayResult["LOAN_PERMIT_BALANCE"] = $maxloan_amt - $dataComing["request_amt"];
			$arrayResult["PERIOD"] = $dataComing["period"];
			$arrayResult["PERIOD_PAYMENT"] = ceil($period_payment);
			$arrayResult["__old_balance"] = ($sum_old_payment);
			$arrayResult["__saraly_balance"] = ($rowMember["SALARY_AMOUNT"]*0.6);
			$arrayResult['CALCULATE_VALUE'] = $calculate_arr;
			
			//คำนวนรายการหัก + ขอกู้ต้องไม่เกิน 60% ของเงินเดือน
			if($rowLoanGroup["LOANGROUP_CODE"] == '02' && ((ceil($period_payment) + $sum_old_payment) > ($rowMember["SALARY_AMOUNT"]*0.6))){
				$canRequest = FALSE;
				$error_desc = "ไม่สามารถยื่นกู้ได้ เนื่องจากท่านมีรายการหักเกิน 60% ของเงินเดือน";
			}else{
				$canRequest = TRUE;
			}
			
			if(!$canRequest){
				$arrayResult['RESPONSE_CODE'] = "WS0084";
				$arrayResult['RESPONSE_MESSAGE'] = $error_desc ?? $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
			
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$maxloan_amt = 0;
			$oldBal = 0;
			$loanRequest = TRUE;
			//เช็ควันเกษียณ สิ้นปี ปีที่ครบ 55 ปี
			$memberInfo = $conmssql->prepare("SELECT mb.BIRTH_DATE,mb.MEMBGROUP_CODE
									FROM mbmembmaster mb
									WHERE mb.member_no = :member_no");
			$memberInfo->execute([':member_no' => $member_no]);
			$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
			$m_birthdate = date('m',strtotime($rowMember["BIRTH_DATE"]));
			$y_birthdate = date('Y')-date('Y',strtotime($rowMember["BIRTH_DATE"]));
			if($rowMember["MEMBGROUP_CODE"] == "SKCM" || $rowMember["MEMBGROUP_CODE"] == "SKLM"){
				$max_member_period = ((60 - $y_birthdate)*12) + (12 - date('m'));
			}else{
				$max_member_period = ((55 - $y_birthdate)*12) + (12 - date('m'));
			}
			
			if(file_exists(__DIR__.'/../credit/calculate_loan_'.$rowLoanGroup["LOANGROUP_CODE"].'.php')){
				include(__DIR__.'/../credit/calculate_loan_'.$rowLoanGroup["LOANGROUP_CODE"].'.php');
			}else{
				include(__DIR__.'/../credit/calculate_loan_etc.php');
			}
			if($maxloan_amt <= 0){
				$arrayResult["LOAN_PERMIT_AMT"] = 0;
				$arrayResult["REQUEST_AMT"] = 0;
				$arrayResult["MAX_PERIOD"] = 0;
				$arrayResult['RESPONSE_CODE'] = "WS0084";
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถยื่นกู้ได้ เนื่องจากท่านมีรายการหักเกิน 60% ของเงินเดือน";
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
			$rowMaxPeriodMain = $getMaxPeriod->fetch(PDO::FETCH_ASSOC);
			
			$getMaxPeriodTemp = $conmssql->prepare("SELECT MAX_PERIOD 
												FROM lnloantype lnt LEFT JOIN lnloantypeperiod lnd ON lnt.LOANTYPE_CODE = lnd.LOANTYPE_CODE
												WHERE lnd.LOANTYPE_CODE = :loantype_code");
			$getMaxPeriodTemp->execute([
				':loantype_code' => $dataComing["loantype_code"]
			]);
			
			$rowMaxPeriodTemp = $getMaxPeriodTemp->fetch(PDO::FETCH_ASSOC);
			$rowMaxPeriod["MAX_PERIOD"] = $rowMaxPeriodMain["MAX_PERIOD"] ?? $rowMaxPeriodTemp["MAX_PERIOD"];
			
			//เช็คงวดเกษียณ
			if($max_member_period < 0){
				$max_member_period = 0;
			}
			$arrMinPeriod = array();
			$arrMinPeriod[] = $max_member_period;
			$arrMinPeriod[] = $rowMaxPeriod["MAX_PERIOD"];
			$rowMaxPeriod["MAX_PERIOD"] =  min($arrMinPeriod);

			if(isset($rowMaxPeriod["MAX_PERIOD"]) && $rowMaxPeriod["MAX_PERIOD"] > 0){
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
				/*$getBank = $conmssql->prepare("SELECT (BANK_DESC) as BANK_DESC,BANK_CODE FROM CMUCFBANK 
											WHERE bank_code IN('002','004','014') and USE_FLAG = '1' ORDER BY SETSORT ASC");
				$getBank->execute();
				while($rowBank = $getBank->fetch(PDO::FETCH_ASSOC)){
					$arrBank = array();
					$arrBank["BANK_DESC"] = $rowBank["BANK_DESC"];
					$arrBank["BANK_CODE"] = $rowBank["BANK_CODE"];
					$arrGrpBank[] = $arrBank;
				}*/

				//ดึงข้อมูลโครงการพิเศษ
				$arrayGroupExtraCredit = array();
				$fetchExtraCredit = $conmysql->prepare("SELECT id_extra_credit, extra_credit_name, extra_credit_desc, extra_credit_html, loantype_code FROM gcconstantextracreditproject WHERE is_use = '1'");
				$fetchExtraCredit->execute();
				while($rowExtraCredit = $fetchExtraCredit->fetch(PDO::FETCH_ASSOC)){
					$arrExtraCredit = array();
					$arrExtraCredit["ID_EXTRA_CREDIT"] = $rowExtraCredit["id_extra_credit"];
					$arrExtraCredit["EXTRA_CREDIT_NAME"] = $rowExtraCredit["extra_credit_name"];
					$arrExtraCredit["EXTRA_CREDIT_DESC"] = $rowExtraCredit["extra_credit_desc"];
					$arrExtraCredit["EXTRA_CREDIT_HTML"] = $rowExtraCredit["extra_credit_html"];
					$arrExtraCredit["LOANTYPE_CODE"] = $rowExtraCredit["loantype_code"];
					$arrayGroupExtraCredit[] = $arrExtraCredit;
				}
				$arrayResult["IS_EXTRA_CREDIT"] = TRUE;
				$arrayResult["EXTRA_CREDIT_LIST"] = $arrayGroupExtraCredit;
					
				if($rowLoanGroup["LOANGROUP_CODE"] == '02'){
					$period_payment = $maxloan_amt / $rowMaxPeriod["MAX_PERIOD"];
				}else{
					$period_payment = $maxloan_amt / $rowMaxPeriod["MAX_PERIOD"];
				}
				
				//เลขบัญชีธนาคาร
				$fetchBankInfo = $conmssql->prepare("SELECT mb.EXPENSE_BANK, mb.EXPENSE_CODE, mb.EXPENSE_ACCID,cmb.BANK_DESC FROM MBMEMBMASTER mb
												LEFT JOIN cmucfbank cmb ON cmb.BANK_CODE = mb.EXPENSE_BANK
												WHERE mb.MEMBER_NO = :member_no");
				$fetchBankInfo->execute([':member_no' => $member_no]);
				$rowBankInfo = $fetchBankInfo->fetch(PDO::FETCH_ASSOC);
				$arrayResult["EXPENSE_BANK"] = $rowBankInfo["EXPENSE_BANK"];
				$arrayResult["EXPENSE_BANK_DESC"] = $rowBankInfo["BANK_DESC"];
				$arrayResult["EXPENSE_CODE"] = $rowBankInfo["EXPENSE_CODE"];
				$arrayResult["EXPENSE_ACCID"] = $rowBankInfo["EXPENSE_ACCID"];
				
				$arrayResult["DIFFOLD_CONTRACT"] = $oldBal;
				$arrRecvBank["VALUE"] = "0";
				$arrRecvBank["DESC"] = "โอนเข้าบัญชีธนาคาร";
				$arrGrpReceive[] = $arrRecvBank;
				/*$arrRecvCoop["VALUE"] = "1";
				$arrRecvCoop["DESC"] = "โอนเข้าบัญชีสหกรณ์";
				$arrGrpReceive[] = $arrRecvCoop;*/
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
				$arrayResult["IS_BANK_ACCOUNT"] = FALSE;
				$arrayResult["BANK_ACCOUNT_REMARK"] = 'หากไม่ใช่บัญชีเงินเดือน กรุณาแนบหน้า Bookbank (ต้องเป็นบัญชีผู้กู้)';
				$arrayResult['OBJECTIVE'] = $arrGrpObj;
				$arrayResult['OLD_CONTRACT'] = $arrOldContract;
				$arrayResult['OLD_GROUPBAL_01'] = $oldGroupBal01;
				$arrayResult['OLD_GROUPBAL_02'] = $oldGroupBal02;
				$arrayResult['CALCULATE_VALUE'] = $calculate_arr;
				$receive_date = null;
				if($rowLoanGroup["LOANGROUP_CODE"] == '01'){
					if(date('w') > 3 || date('w') == 0){
						$receive_date = date( 'Y-m-d', strtotime( 'friday next week' ) );
						$arrayResult['RECEIVE_DATE_TEXT'] = $lib->convertdate($receive_date,'D m Y');
						$arrayResult['RECEIVE_DATE'] = $receive_date;
					}else{
						$receive_date = date( 'Y-m-d', strtotime( 'friday this week' ) );
						$arrayResult['RECEIVE_DATE_TEXT'] = $lib->convertdate($receive_date,'D m Y');
						$arrayResult['RECEIVE_DATE'] = $receive_date;
					}
				}else{
					if(date('d')>=1 && date('d')<=10){
						$middle_month = new DateTime(date('Y')."-".date('m')."-20");
						$middle_month = $middle_month->format('Y-m-d');
						$getLoanPayDate = $conmysql->prepare("SELECT loanpaydate FROM gcconstantloanpaydate WHERE is_use = '1' AND :middle_month < loanpaydate ORDER BY loanpaydate ASC LIMIT 1");
						$getLoanPayDate->execute([':middle_month' => $middle_month]);
						$rowLoanPayDate = $getLoanPayDate->fetch(\PDO::FETCH_ASSOC);
						if(isset($rowLoanPayDate["loanpaydate"])){
							if(date('w', strtotime($rowLoanPayDate["loanpaydate"])) == 0 || date('w', strtotime($rowLoanPayDate["loanpaydate"])) == 6){
								$receive_date = date('Y-m-d', strtotime($rowLoanPayDate["loanpaydate"]." friday this week"));
								$arrayResult['RECEIVE_DATE_TEXT'] = $lib->convertdate($receive_date,'D m Y');
								$arrayResult['RECEIVE_DATE'] = $receive_date;
							}else{
								$receive_date = date('Y-m-d', strtotime($rowLoanPayDate["loanpaydate"]));
								$arrayResult['RECEIVE_DATE_TEXT'] = $lib->convertdate($receive_date,'D m Y');
								$arrayResult['RECEIVE_DATE'] = $receive_date;
							}
						}else{
							$arrayResult['RECEIVE_DATE_TEXT'] = null;
							$arrayResult['RECEIVE_DATE'] = null;
						}
					}else if(date('d')>=20){
						#mid month
						$mid_date = new DateTime(date('Y')."-".date('m')."-15");
						$mid_date->modify('+1 month');
						$mid_date = $mid_date->format('Y-m-d');
						
						$mid_date_from = new DateTime(date('Y')."-".date('m')."-01");
						$mid_date_from->modify('+1 month');
						$mid_date_from = $mid_date_from->format('Y-m-d');
						$mid_date_to = new DateTime(date('Y')."-".date('m')."-20");
						$mid_date_to->modify('+1 month');
						$mid_date_to = $mid_date_to->format('Y-m-d');
						
						$getLoanPayDate = $conmysql->prepare("SELECT loanpaydate FROM gcconstantloanpaydate 
										WHERE is_use = '1' AND :mid_date_from <= loanpaydate AND :mid_date_to >= loanpaydate 
										ORDER BY loanpaydate ASC LIMIT 1");
						$getLoanPayDate->execute([
							':mid_date_from' => $mid_date_from,
							':mid_date_to' => $mid_date_to
						]);
						$rowLoanPayDate = $getLoanPayDate->fetch(\PDO::FETCH_ASSOC);
						
						if(isset($rowLoanPayDate["loanpaydate"])){
							if(date('w', strtotime($rowLoanPayDate["loanpaydate"])) == 0 || date('w', strtotime($rowLoanPayDate["loanpaydate"])) == 6){
								$receive_date = date('Y-m-d', strtotime($rowLoanPayDate["loanpaydate"]." friday this week"));
								$arrayResult['RECEIVE_DATE_TEXT'] = $lib->convertdate($receive_date,'D m Y');
								$arrayResult['RECEIVE_DATE'] = $receive_date;
							}else{
								$receive_date = date('Y-m-d', strtotime($rowLoanPayDate["loanpaydate"]));
								$arrayResult['RECEIVE_DATE_TEXT'] = $lib->convertdate($receive_date,'D m Y');
								$arrayResult['RECEIVE_DATE'] = $receive_date;
							}
						}else{
							if(date('w', strtotime($mid_date)) == 0 || date('w', strtotime($mid_date)) == 6){
								$receive_date = date('Y-m-d', strtotime($mid_date." friday this week"));
								$arrayResult['RECEIVE_DATE_TEXT'] = $lib->convertdate($receive_date,'D m Y');
								$arrayResult['RECEIVE_DATE'] = $receive_date;
							}else{
								$receive_date = date('Y-m-d', strtotime($mid_date));
								$arrayResult['RECEIVE_DATE_TEXT'] = $lib->convertdate($receive_date,'D m Y');
								$arrayResult['RECEIVE_DATE'] = $receive_date;
							}
						}
					}else{
						$arrayResult['RECEIVE_DATE'] = null;
					}
				}
				
				//$arrayResult['BANK'] = $arrGrpBank;
				//คำนวนรายการหัก + ขอกู้ต้องไม่เกิน 60% ของเงินเดือน
				if($rowLoanGroup["LOANGROUP_CODE"] == '02' && ((ceil($period_payment) + $sum_old_payment) > ($rowMember["SALARY_AMOUNT"]*0.6))){
					$canRequest = FALSE;
					$error_desc = "ไม่สามารถยื่นกู้ได้ เนื่องจากท่านมีรายการหักเกิน 60% ของเงินเดือน";
				}else{
					$canRequest = TRUE;
				}
				if($dayremainEnd == 0){
					$arrayResult["NOTE_DESC"] = "เงินต้นและดอกเบี้ยของท่าน ณ วันที่กู้จะถูกหักรวมกับยอดปันผล-เฉลี่ยคืนที่ท่านจะได้รับ";
					$arrayResult["NOTE_DESC_COLOR"] = "red";
				}
				if(!$canRequest){
					$arrayResult['RESPONSE_CODE'] = "WS0084";
					$arrayResult['RESPONSE_MESSAGE'] = $error_desc ?? $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0088";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESPONSE'] = [
							':request_amt_1' => $maxloan_amt,
							':request_amt_2' => $maxloan_amt,
							':loantype_code' => $dataComing["loantype_code"]
						];
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