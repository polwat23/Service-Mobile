<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepPayLoan')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupAccAllow = array();
		$arrGroupAccFav = array();
		$arrLoanGrp = array();
		$arrayAcc = array();
		$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
													LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
													WHERE gat.member_no = :member_no and gat.is_use = '1' and gad.allow_pay_loan = '1'");
		$fetchAccAllowTrans->execute([':member_no' => $payload["member_no"]]);
		if($fetchAccAllowTrans->rowCount() > 0){
			while($rowAccAllow = $fetchAccAllowTrans->fetch(PDO::FETCH_ASSOC)){
				$arrayAcc[] = "'".$rowAccAllow["deptaccount_no"]."'";
			}
			$getDataBalAcc = $conoracle->prepare("SELECT dpm.deptaccount_no,dpm.deptaccount_name,dpt.depttype_desc,dpm.withdrawable_amt as prncbal,dpm.depttype_code
													FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
													WHERE dpm.deptaccount_no IN(".implode(',',$arrayAcc).") and dpm.acccont_type = '01' and dpm.deptclose_status = 0
													ORDER BY dpm.deptaccount_no ASC");
			$getDataBalAcc->execute();
			while($rowDataAccAllow = $getDataBalAcc->fetch(PDO::FETCH_ASSOC)){
				$checkSeqAmt = $cal_dep->getSequestAmount($rowDataAccAllow["DEPTACCOUNT_NO"],'WTX');
				if($checkSeqAmt["RESULT"]){
					if($checkSeqAmt["CAN_WITHDRAW"]){
						$arrAccAllow = array();
						if(file_exists(__DIR__.'/../../resource/dept-type/'.$rowDataAccAllow["DEPTTYPE_CODE"].'.png')){
							$arrAccAllow["DEPT_TYPE_IMG"] = $config["URL_SERVICE"].'resource/dept-type/'.$rowDataAccAllow["DEPTTYPE_CODE"].'.png?v='.date('Ym');
						}else{
							$arrAccAllow["DEPT_TYPE_IMG"] = null;
						}
						$arrAccAllow["DEPTACCOUNT_NO"] = $rowDataAccAllow["DEPTACCOUNT_NO"];
						$arrAccAllow["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowDataAccAllow["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
						$arrAccAllow["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($rowDataAccAllow["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
						$arrAccAllow["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAccAllow["DEPTACCOUNT_NAME"]);
						$arrAccAllow["DEPT_TYPE"] = $rowDataAccAllow["DEPTTYPE_DESC"];
						$arrAccAllow["BALANCE"] = $checkSeqAmt["SEQUEST_AMOUNT"] ?? $cal_dep->getWithdrawable($rowDataAccAllow["DEPTACCOUNT_NO"]);
						$arrAccAllow["BALANCE_FORMAT"] = number_format($arrAccAllow["BALANCE"],2);
						$arrGroupAccAllow[] = $arrAccAllow;
					}else{
						$arrayResult['RESPONSE_CODE'] = "WS0104";
						$arrayResult['RESPONSE_MESSAGE'] = $checkSeqAmt["SEQUEST_DESC"];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = $checkSeqAmt["RESPONSE_CODE"];
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}
			$getAccFav = $conmysql->prepare("SELECT gts.destination,gfl.name_fav
												FROM gcfavoritelist gfl LEFT JOIN gctransaction gts ON gfl.ref_no = gts.ref_no
												and gfl.member_no = gts.member_no
												WHERE gfl.member_no = :member_no and gfl.is_use = '1' and gts.destination_type = '3'");
			$getAccFav->execute([':member_no' => $payload["member_no"]]);
			while($rowAccFav = $getAccFav->fetch(PDO::FETCH_ASSOC)){
				$arrAccFav = array();
				$arrAccFav["DESTINATION"] = $rowAccFav["destination"];
				$arrAccFav["NAME_FAV"] = $rowAccFav["name_fav"];
				$arrGroupAccFav[] = $arrAccFav;
			}
			$fetchLoanRepay = $conoracle->prepare("SELECT lnt.loantype_desc,lnm.loancontract_no,lnm.interest_arrear,lnm.intpayable_amt,lnm.principal_balance,
													lnm.period_payamt,lnm.last_periodpay,lnm.LOANTYPE_CODE
													FROM lncontmaster lnm LEFT JOIN lnloantype lnt ON lnm.LOANTYPE_CODE = lnt.LOANTYPE_CODE 
													WHERE lnm.member_no = :member_no and lnm.contract_status = 1 and lnm.principal_balance > 0");
			$fetchLoanRepay->execute([':member_no' => $member_no]);
			while($rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC)){
				$arrLoan = array();
				try {
					$clientWSLoan = new SoapClient($config["URL_CORE_COOP"]."n_loan.svc?singleWsdl");
					try {
						$argumentWSLoan = [
							"as_wspass" => $config["WS_STRC_DB"],
							"as_coopid" => $config["COOP_ID"],
							"as_contno" => $rowLoan["LOANCONTRACT_NO"],
							"adtm_lastcalint" => date('c')							
						];
						$resultWSLoan = $clientWSLoan->__call("of_computeinterest", array($argumentWSLoan));
						$arrLoan["INT_BALANCE"] = $resultWSLoan->of_computeinterestResult ?? "0.00";
					}catch(Throwable $e){
						$arrLoan["INT_BALANCE"] = "0.00";
					}
				}catch(Throwable $e){
					$arrLoan["INT_BALANCE"] = "0.00";
				}
				if(file_exists(__DIR__.'/../../resource/loan-type/'.$rowLoan["LOANTYPE_CODE"].'.png')){
					$arrLoan["LOAN_TYPE_IMG"] = $config["URL_SERVICE"].'resource/loan-type/'.$rowLoan["LOANTYPE_CODE"].'.png?v='.date('Ym');
				}else{
					$arrLoan["LOAN_TYPE_IMG"] = null;
				}
				$getLoanConstant = $conmysql->prepare("SELECT loantype_alias_name FROM gcconstanttypeloan WHERE loantype_code = :loantype_code");
				$getLoanConstant->execute([':loantype_code' => $rowLoan["LOANTYPE_CODE"]]);
				$rowLoanCont = $getLoanConstant->fetch(PDO::FETCH_ASSOC);
				$arrLoan["LOAN_TYPE"] = $rowLoanCont["loantype_alias_name"] ?? $rowLoan["LOANTYPE_DESC"];
				$arrLoan["CONTRACT_NO"] = $rowLoan["LOANCONTRACT_NO"];
				$arrLoan["BALANCE"] = number_format($rowLoan["PRINCIPAL_BALANCE"],2);
				$arrLoan["PERIOD_ALL"] = number_format($rowLoan["PERIOD_PAYAMT"],0);
				$arrLoan["PERIOD_BALANCE"] = number_format($rowLoan["LAST_PERIODPAY"],0);
				$arrLoan["SUM_BALANCE"] = number_format($rowLoan["PRINCIPAL_BALANCE"] + $arrLoan["INT_BALANCE"] + $rowLoan["INTEREST_ARREAR"] + $rowLoan["INTPAYABLE_AMT"],2);
				$arrLoanGrp[] = $arrLoan;
			}
			if(sizeof($arrGroupAccAllow) > 0 || sizeof($arrGroupAccFav) > 0){
				$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
				$arrayResult['ACCOUNT_FAV'] = $arrGroupAccFav;
				$arrayResult['LOAN'] = $arrLoanGrp;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0023";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0023";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
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