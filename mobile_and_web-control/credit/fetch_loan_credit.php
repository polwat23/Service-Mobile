<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanCredit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrLoanAllow = array();
		$getLoanCredit = $conoracle->prepare("SELECT loantype_code FROM gcconstanttypeloan WHERE is_creditloan = '1'");
		$getLoanCredit->execute();
		while($rowCreditAllow = $getLoanCredit->fetch(PDO::FETCH_ASSOC)){
			$arrLoanAllow[] = "'".$rowCreditAllow["LOANTYPE_CODE"]."'";
		}
		$arrCreditGrp = array();
		$checkMembtype = $conoracle->prepare("SELECT lt.loantype_desc,lt.loantype_code FROM lnloantype lt 
											LEFT JOIN lnloantypembtype lm ON lt.loantype_code = lm.loantype_code
											LEFT JOIN mbmembmaster mb ON lm.membtype_code = mb.membtype_code
											WHERE mb.member_no = :member_no and lt.loantype_code IN(".implode(',',$arrLoanAllow).")");
		$checkMembtype->execute([':member_no' => $member_no]);
		try {
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_loan.svc?singleWsdl");
			while($rowLoanAllow = $checkMembtype->fetch(PDO::FETCH_ASSOC)){
				$structureReqLoan = array();
				$structureReqLoan["coop_id"] = $config["COOP_ID"];
				$structureReqLoan["member_no"] = $member_no;
				$structureReqLoan["loantype_code"] = $rowLoanAllow["LOANTYPE_CODE"];
				$structureReqLoan["operate_date"] = date("c");
				try {
					$argumentWS = [
						"as_wspass" => $config["WS_STRC_DB"],
						"atr_lnatm" => $structureReqLoan
					];
					$resultWS = $clientWS->__call("of_getloanpermiss_IVR", array($argumentWS));
					$responseSoap = $resultWS->atr_lnatm;
					$arrCredit["LOANTYPE_DESC"] = $rowLoanAllow["LOANTYPE_DESC"];
					$arrCredit["LOANTYPE_CODE"] = $rowLoanAllow["LOANTYPE_CODE"];
					$arrCredit["BUY_SHARE_MORE"] = $responseSoap->buyshr_amt ?? 0;
					$arrCredit['LOAN_PERMIT_AMT'] = $responseSoap->loanpermiss_amt ?? 0;
					$arrLoanClr = explode(',',$responseSoap->contclr_no);
					$arrOldContract = array();
					foreach($arrLoanClr as $loancontract_no){
						if(isset($loancontract_no) && $loancontract_no != ""){
							$getContDetail = $conoracle->prepare("SELECT lm.principal_balance,lt.loantype_desc FROM lncontmaster lm LEFT JOIN lnloantype lt 
															ON lm.loantype_code = lt.loantype_code WHERE lm.loancontract_no = :contract_no");
							$getContDetail->execute([':contract_no' => $loancontract_no]);
							$rowContDetail = $getContDetail->fetch(PDO::FETCH_ASSOC);
							$arrContract = array();
							$arrContract['LOANTYPE_DESC'] = $rowContDetail["LOANTYPE_DESC"];
							$arrContract['CONTRACT_NO'] = $loancontract_no;
							$arrContract['BALANCE'] = $rowContDetail["PRINCIPAL_BALANCE"];
							$arrOldContract[] = $arrContract;
						}
					}
					$arrCredit["OLD_CONTRACT"] = $arrOldContract;
					$arrCreditGrp[] = $arrCredit;
				}catch(SoapFault $e){
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS0058",
						":error_desc" => "คำนวณสิทธิ์กู้ไม่ได้ ประเภท ".$rowCreditAllow["loantype_code"]."\n"."Error => ".($e->getMessage() ?? " Service ไม่ได้ Return Error มาให้"),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$arrayResult['RESPONSE_CODE'] = "WS0058";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}
			$arrayResult["LOAN_CREDIT"] = $arrCreditGrp;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}catch(Throwable $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0058",
				":error_desc" => "คำนวณสิทธิ์กู้ไม่ได้ "."\n"."Error => ".$e->getMessage(),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." คำนวณสิทธิ์กู้ไม่ได้ "."\n"."DATA => ".json_encode($dataComing)."\n"."Error => ".$e->getMessage();
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_CODE'] = "WS0058";
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